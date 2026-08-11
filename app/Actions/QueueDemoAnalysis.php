<?php

namespace App\Actions;

use App\AnalysisStatus;
use App\Jobs\ProcessDemoAnalysis;
use App\Models\Analysis;
use App\Models\Demo;
use DomainException;
use Illuminate\Support\Facades\DB;

class QueueDemoAnalysis
{
    public function handle(Demo $demo, bool $manualRetry = false): Analysis
    {
        return DB::transaction(function () use ($demo, $manualRetry): Analysis {
            $lockedDemo = Demo::query()->whereKey($demo)->lockForUpdate()->firstOrFail();

            if ($lockedDemo->uploaded_at === null) {
                throw new DomainException('The demo must be uploaded before it can be analyzed.');
            }

            $latestAnalysis = $lockedDemo->analyses()->latest('attempt')->first();

            if ($latestAnalysis !== null && $latestAnalysis->status !== AnalysisStatus::Failed) {
                return $latestAnalysis;
            }

            if ($manualRetry && $latestAnalysis?->status !== AnalysisStatus::Failed) {
                throw new DomainException('Only a failed analysis may be retried.');
            }

            $analysis = $lockedDemo->analyses()->create([
                'attempt' => ($latestAnalysis?->attempt ?? 0) + 1,
                'status' => AnalysisStatus::Queued,
                'schema_version' => WorkerContract::SchemaVersion,
            ]);

            $lockedDemo->update(['status' => AnalysisStatus::Queued]);

            ProcessDemoAnalysis::dispatch(
                analysisId: $analysis->id,
                demoId: $lockedDemo->id,
                checksumSha256: $lockedDemo->checksum_sha256,
            )->afterCommit();

            return $analysis;
        });
    }
}
