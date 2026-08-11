<?php

namespace App\Actions;

use App\Models\Analysis;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class RunDemoParser
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Analysis $analysis): array
    {
        $analysis->loadMissing('demo');

        $outputDirectory = storage_path("app/private/analyses/{$analysis->id}");
        File::ensureDirectoryExists($outputDirectory);

        $process = new Process([
            (string) config('demo_analysis.uv_binary'),
            'run',
            '--project',
            base_path('worker'),
            'clutch-worker',
            '--bucket',
            (string) config("filesystems.disks.{$analysis->demo->storage_disk}.bucket"),
            '--key',
            $analysis->demo->storage_path,
            '--output',
            $outputDirectory,
            '--expected-sha256',
            $analysis->demo->checksum_sha256,
        ], base_path());
        $process->setTimeout((float) config('demo_analysis.process_timeout'));

        try {
            $process->run();
        } catch (Throwable $exception) {
            throw DemoParserException::fromThrowable($exception);
        }

        try {
            $payload = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw DemoParserException::fromThrowable($exception);
        }

        if (! is_array($payload) || ! app(WorkerContract::class)->validate($payload['ok'] === true ? 'output' : 'error', $payload)) {
            throw DemoParserException::fromWorker('invalid_worker_response', 'The demo parser returned an invalid response.', [
                'exit_code' => $process->getExitCode(),
                'stderr' => mb_substr(trim($process->getErrorOutput()), 0, 2000),
            ]);
        }

        if ($payload['ok'] === false) {
            throw DemoParserException::fromWorker(
                $payload['error']['code'],
                $payload['error']['message'],
                [
                    'retryable' => $payload['error']['retryable'],
                    'details' => $payload['error']['details'],
                    'exit_code' => $process->getExitCode(),
                ],
            );
        }

        return $payload;
    }
}
