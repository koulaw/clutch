<?php

use App\Actions\StoreAnalysisArtifacts;
use App\AnalysisStatus;
use App\Models\Analysis;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

it('stores versioned analytical and per-round replay artifacts', function () {
    Storage::fake('s3');
    $analysis = Analysis::factory()->create(['status' => AnalysisStatus::Analyzing]);
    $output = storage_path("app/private/analyses/{$analysis->id}");
    File::ensureDirectoryExists("{$output}/events");
    File::ensureDirectoryExists("{$output}/replays");

    $files = [
        'match.json' => json_encode([
            'map_name' => 'de_mirage',
            'patch_version' => 14174,
            'tick_rate' => 64,
        ], JSON_THROW_ON_ERROR),
        'rounds.parquet' => 'complete rounds parquet',
        'players.parquet' => 'complete players parquet',
        'ticks.parquet' => 'complete ticks parquet',
        'events/player_death.parquet' => 'complete deaths parquet',
        'replays/round-1.json.gz' => gzencode('{"round":1,"frames":[]}'),
    ];

    foreach ($files as $path => $contents) {
        File::put("{$output}/{$path}", $contents);
    }

    $payload = [
        'ok' => true,
        'schema_version' => '1.0.0',
        'parser_version' => '2.0.2',
        'output_directory' => $output,
        'manifest' => [
            'schema_version' => '1.0.0',
            'parser_version' => '2.0.2',
            'match' => ['path' => 'match.json'],
            'rounds' => ['path' => 'rounds.parquet', 'rows' => 1],
            'players' => ['path' => 'players.parquet', 'rows' => 10],
            'ticks' => ['path' => 'ticks.parquet', 'rows' => 1_000],
            'events' => ['player_death' => ['path' => 'events/player_death.parquet', 'rows' => 8]],
            'replays' => [[
                'path' => 'replays/round-1.json.gz',
                'round' => 1,
                'start_tick' => 100,
                'freeze_end_tick' => 200,
                'end_tick' => 500,
                'winner_side' => 'ct',
                'win_reason' => 't_killed',
                'frames' => 100,
                'frames_per_second' => 16,
                'version' => '1.0.0',
            ]],
        ],
    ];

    app(StoreAnalysisArtifacts::class)->handle($analysis, $payload);

    $analysis->refresh();
    $replay = $analysis->artifacts()->where('type', 'replay')->sole();

    expect($analysis->status)->toBe(AnalysisStatus::Ready)
        ->and($analysis->completed_at)->not->toBeNull()
        ->and($analysis->demo->refresh()->status)->toBe(AnalysisStatus::Ready)
        ->and($analysis->artifacts()->count())->toBe(6)
        ->and($analysis->gameMatch->map_name)->toBe('de_mirage')
        ->and($analysis->gameMatch->mapRadar->version)->toBe('84adbb9dca5a')
        ->and($analysis->gameMatch->rounds()->sole()->number)->toBe(1)
        ->and($replay->gameRound->number)->toBe(1)
        ->and($replay->metadata)->toMatchArray([
            'frames' => 100,
            'frames_per_second' => 16,
            'compression' => 'gzip',
        ])
        ->and($replay->size_bytes)->toBe(mb_strlen($files['replays/round-1.json.gz'], '8bit'))
        ->and($replay->checksum_sha256)->toHaveLength(64)
        ->and($replay->version)->toBe('1.0.0')
        ->and($analysis->demo->user->quota->stored_analyses)->toBe(1)
        ->and(File::isDirectory($output))->toBeFalse();

    foreach ($analysis->artifacts as $artifact) {
        Storage::disk('s3')->assertExists($artifact->storage_path);
    }
});

it('rejects artifact paths outside the analysis output directory', function () {
    Storage::fake('s3');
    $analysis = Analysis::factory()->create(['status' => AnalysisStatus::Analyzing]);

    expect(fn () => app(StoreAnalysisArtifacts::class)->handle($analysis, [
        'schema_version' => '1.0.0',
        'parser_version' => '2.0.2',
        'manifest' => [
            'schema_version' => '1.0.0',
            'match' => ['path' => '../secret'],
            'rounds' => ['path' => 'rounds.parquet', 'rows' => 0],
            'players' => ['path' => 'players.parquet', 'rows' => 0],
            'ticks' => ['path' => 'ticks.parquet', 'rows' => 0],
            'events' => [],
            'replays' => [],
        ],
    ]))->toThrow(InvalidArgumentException::class);
});
