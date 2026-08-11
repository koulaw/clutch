<?php

namespace App\Actions;

use App\AnalysisStatus;
use App\Models\Analysis;
use App\Models\Artifact;
use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class StoreAnalysisArtifacts
{
    public function __construct(private ManageUserQuota $quotas) {}

    /** @param array<string, mixed> $payload */
    public function handle(Analysis $analysis, array $payload): void
    {
        $analysis->loadMissing('demo.user');
        $manifest = $payload['manifest'];
        $outputDirectory = storage_path("app/private/analyses/{$analysis->id}");
        $artifacts = $this->artifactDescriptors($manifest);

        foreach ($artifacts as &$artifact) {
            $localPath = $this->localPath($outputDirectory, $artifact['path']);
            $storagePath = "artifacts/{$analysis->demo->public_id}/attempt-{$analysis->attempt}/{$artifact['path']}";
            $stream = fopen($localPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException("Unable to read artifact {$artifact['path']}.");
            }

            try {
                if (! Storage::disk($analysis->demo->storage_disk)->put($storagePath, $stream)) {
                    throw new RuntimeException("Unable to store artifact {$artifact['path']}.");
                }
            } finally {
                fclose($stream);
            }

            $artifact['storage_path'] = $storagePath;
            $artifact['size_bytes'] = File::size($localPath);
            $artifact['checksum_sha256'] = hash_file('sha256', $localPath);
        }
        unset($artifact);

        DB::transaction(function () use ($analysis, $manifest, $artifacts, $payload): void {
            $lockedAnalysis = Analysis::query()->whereKey($analysis)->lockForUpdate()->firstOrFail();

            if ($lockedAnalysis->status === AnalysisStatus::Ready) {
                return;
            }

            $match = $this->storeMatchAndRounds($lockedAnalysis, $manifest);

            foreach ($artifacts as $artifact) {
                $gameRoundId = null;
                if ($artifact['round'] !== null) {
                    $gameRoundId = $match->rounds()->where('number', $artifact['round'])->value('id');
                }

                Artifact::query()->updateOrCreate(
                    [
                        'storage_disk' => $lockedAnalysis->demo->storage_disk,
                        'storage_path' => $artifact['storage_path'],
                    ],
                    [
                        'analysis_id' => $lockedAnalysis->id,
                        'game_round_id' => $gameRoundId,
                        'type' => $artifact['type'],
                        'size_bytes' => $artifact['size_bytes'],
                        'checksum_sha256' => $artifact['checksum_sha256'],
                        'version' => $artifact['version'],
                        'metadata' => $artifact['metadata'],
                    ],
                );
            }

            $this->quotas->storeAnalysis($lockedAnalysis->demo->user);
            $lockedAnalysis->update([
                'status' => AnalysisStatus::Ready,
                'schema_version' => $payload['schema_version'],
                'parser_version' => $payload['parser_version'],
                'completed_at' => now(),
            ]);
            $lockedAnalysis->demo->update(['status' => AnalysisStatus::Ready]);
        });

        File::deleteDirectory($outputDirectory);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array{path: string, type: string, version: string, round: ?int, metadata: array<string, mixed>, storage_path?: string, size_bytes?: int, checksum_sha256?: string}>
     */
    private function artifactDescriptors(array $manifest): array
    {
        $version = $manifest['schema_version'];
        $artifacts = [
            $this->descriptor($manifest['match'], 'analytics_match', $version),
            $this->descriptor($manifest['rounds'], 'analytics_rounds', $version),
            $this->descriptor($manifest['players'], 'analytics_players', $version),
            $this->descriptor($manifest['ticks'], 'analytics_ticks', $version),
        ];

        foreach ($manifest['events'] as $name => $event) {
            $artifacts[] = $this->descriptor($event, "analytics_event:{$name}", $version, metadata: ['event' => $name]);
        }

        foreach ($manifest['replays'] ?? [] as $replay) {
            $artifacts[] = $this->descriptor(
                $replay,
                'replay',
                $replay['version'],
                round: $replay['round'],
                metadata: [
                    'frames' => $replay['frames'],
                    'frames_per_second' => $replay['frames_per_second'],
                    'compression' => 'gzip',
                ],
            );
        }

        return $artifacts;
    }

    /**
     * @param  array<string, mixed>  $artifact
     * @param  array<string, mixed>  $metadata
     * @return array{path: string, type: string, version: string, round: ?int, metadata: array<string, mixed>}
     */
    private function descriptor(array $artifact, string $type, string $version, ?int $round = null, array $metadata = []): array
    {
        if (isset($artifact['rows'])) {
            $metadata['rows'] = $artifact['rows'];
        }

        return [
            'path' => $artifact['path'],
            'type' => $type,
            'version' => $version,
            'round' => $round,
            'metadata' => $metadata,
        ];
    }

    private function localPath(string $outputDirectory, string $relativePath): string
    {
        if ($relativePath === '' || str_starts_with($relativePath, '/') || str_contains($relativePath, '..')) {
            throw new InvalidArgumentException('The worker returned an unsafe artifact path.');
        }

        $path = realpath($outputDirectory.DIRECTORY_SEPARATOR.$relativePath);
        $base = realpath($outputDirectory);

        if ($path === false || $base === false || ! str_starts_with($path, $base.DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException("Artifact {$relativePath} is missing or outside the output directory.");
        }

        return $path;
    }

    /** @param array<string, mixed> $manifest */
    private function storeMatchAndRounds(Analysis $analysis, array $manifest): GameMatch
    {
        $matchPath = $this->localPath(storage_path("app/private/analyses/{$analysis->id}"), $manifest['match']['path']);
        $header = json_decode(File::get($matchPath), true, flags: JSON_THROW_ON_ERROR);
        $replays = $manifest['replays'] ?? [];
        $tickRate = (int) ($header['tick_rate'] ?? 64);

        $match = $analysis->gameMatch()->updateOrCreate([], [
            'map_name' => $header['map_name'],
            'tick_rate' => $tickRate,
            'duration_ms' => $this->durationMilliseconds($replays, $tickRate),
            'metadata' => ['header' => $header],
        ]);

        foreach ($replays as $round) {
            $match->rounds()->updateOrCreate(['number' => $round['round']], [
                'start_tick' => $round['start_tick'],
                'freeze_end_tick' => $round['freeze_end_tick'],
                'end_tick' => $round['end_tick'],
                'winner_side' => $round['winner_side'],
                'win_reason' => $round['win_reason'],
            ]);
        }

        return $match;
    }

    /** @param list<array<string, mixed>> $replays */
    private function durationMilliseconds(array $replays, int $tickRate): ?int
    {
        if ($replays === [] || $tickRate <= 0) {
            return null;
        }

        $first = $replays[0]['start_tick'];
        $last = $replays[array_key_last($replays)]['end_tick'];

        return (int) round((($last - $first) / $tickRate) * 1000);
    }
}
