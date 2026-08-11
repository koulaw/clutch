<?php

namespace App\Jobs;

use App\Actions\DemoParserException;
use App\Actions\RunDemoParser;
use App\AnalysisStatus;
use App\Models\Analysis;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDemoAnalysis implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 930;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public int $uniqueFor = 3600;

    public function __construct(
        public int $analysisId,
        public int $demoId,
        public string $checksumSha256,
    ) {
        $this->onConnection((string) config('demo_analysis.queue_connection'));
        $this->onQueue((string) config('demo_analysis.queue'));
    }

    public function handle(RunDemoParser $parser): void
    {
        $analysis = Analysis::query()->with('demo')->find($this->analysisId);

        if ($analysis === null || $analysis->status !== AnalysisStatus::Queued) {
            return;
        }

        if (! hash_equals($analysis->demo->checksum_sha256, $this->checksumSha256)) {
            throw DemoParserException::fromWorker('checksum_changed', 'The queued checksum no longer matches the demo.', []);
        }

        $analysis->update(['status' => AnalysisStatus::Parsing, 'started_at' => now()]);
        $analysis->demo->update(['status' => AnalysisStatus::Parsing]);

        try {
            $payload = $parser->handle($analysis);
        } catch (DemoParserException $exception) {
            if (($exception->workerContext['retryable'] ?? true) === false) {
                $this->recordFailure($exception);

                return;
            }

            $analysis->update(['status' => AnalysisStatus::Queued]);
            $analysis->demo->update(['status' => AnalysisStatus::Queued]);

            throw $exception;
        }

        $analysis->update([
            'status' => AnalysisStatus::Analyzing,
            'parser_version' => $payload['parser_version'],
        ]);
        $analysis->demo->update(['status' => AnalysisStatus::Analyzing]);
    }

    public function uniqueId(): string
    {
        return $this->demoId.':'.$this->checksumSha256;
    }

    public function failed(?Throwable $exception): void
    {
        $failure = $exception instanceof DemoParserException
            ? $exception
            : DemoParserException::fromThrowable($exception ?? new \RuntimeException('Unknown queue failure.'));

        $this->recordFailure($failure);
    }

    private function recordFailure(DemoParserException $exception): void
    {
        $analysis = Analysis::query()->with('demo')->find($this->analysisId);

        if ($analysis === null || in_array($analysis->status, [AnalysisStatus::Ready, AnalysisStatus::Unsupported], true)) {
            return;
        }

        $context = array_merge($exception->workerContext, [
            'analysis_id' => $analysis->id,
            'demo_id' => $analysis->demo_id,
            'demo_public_id' => $analysis->demo->public_id,
            'checksum_sha256' => $this->checksumSha256,
            'attempt' => $analysis->attempt,
            'queue' => config('demo_analysis.queue'),
        ]);

        $analysis->update([
            'status' => AnalysisStatus::Failed,
            'failed_at' => now(),
            'error_code' => $exception->workerCode,
            'error_message' => $exception->getMessage(),
            'error_context' => $context,
        ]);
        $analysis->demo->update(['status' => AnalysisStatus::Failed]);

        Log::error('Demo analysis failed.', $context + ['error_code' => $exception->workerCode]);
    }
}
