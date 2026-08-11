<?php

use App\Actions\DemoParserException;
use App\Actions\QueueDemoAnalysis;
use App\Actions\RunDemoParser;
use App\Actions\StoreAnalysisArtifacts;
use App\AnalysisStatus;
use App\Jobs\ProcessDemoAnalysis;
use App\Models\Analysis;
use App\Models\Demo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

uses(LazilyRefreshDatabase::class);

it('queues one checksum-bound analysis on the dedicated queue', function () {
    Queue::fake();
    $demo = Demo::factory()->create();
    $analyses = app(QueueDemoAnalysis::class);

    $first = $analyses->handle($demo);
    $second = $analyses->handle($demo->refresh());

    expect($second->is($first))->toBeTrue()
        ->and($demo->refresh()->status)->toBe(AnalysisStatus::Queued);

    Queue::assertPushed(ProcessDemoAnalysis::class, 1);
    Queue::assertPushed(ProcessDemoAnalysis::class, function (ProcessDemoAnalysis $job) use ($demo, $first): bool {
        return $job->analysisId === $first->id
            && $job->demoId === $demo->id
            && $job->checksumSha256 === $demo->checksum_sha256
            && $job->connection === 'redis'
            && $job->queue === 'demo-analysis'
            && $job->tries === 4
            && $job->timeout === 930
            && $job->backoff === [30, 120, 600];
    });
});

it('tracks parsing and hands a successful worker result to artifact analysis', function () {
    $analysis = Analysis::factory()->create(['status' => AnalysisStatus::Queued]);
    $parser = $this->mock(RunDemoParser::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andReturn([
            'ok' => true,
            'schema_version' => '1.0.0',
            'parser_version' => '0.6.0',
            'output_directory' => '/tmp/output',
            'manifest' => [],
        ]);
    });
    $artifacts = $this->mock(StoreAnalysisArtifacts::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once();
    });

    (new ProcessDemoAnalysis($analysis->id, $analysis->demo_id, $analysis->demo->checksum_sha256))->handle($parser, $artifacts);

    expect($analysis->refresh()->status)->toBe(AnalysisStatus::Analyzing)
        ->and($analysis->parser_version)->toBe('0.6.0')
        ->and($analysis->started_at)->not->toBeNull()
        ->and($analysis->demo->refresh()->status)->toBe(AnalysisStatus::Analyzing);
});

it('records non-retryable worker failures with actionable context', function () {
    $analysis = Analysis::factory()->create(['status' => AnalysisStatus::Queued]);
    $parser = $this->mock(RunDemoParser::class, function (MockInterface $mock): void {
        $exception = DemoParserException::fromWorker('checksum_mismatch', 'Checksum mismatch.', [
            'retryable' => false,
            'details' => ['expected' => 'abc'],
        ]);
        $mock->shouldReceive('handle')->once()->andThrow($exception);
    });
    $artifacts = $this->mock(StoreAnalysisArtifacts::class);

    (new ProcessDemoAnalysis($analysis->id, $analysis->demo_id, $analysis->demo->checksum_sha256))->handle($parser, $artifacts);

    $analysis->refresh();

    expect($analysis->status)->toBe(AnalysisStatus::Failed)
        ->and($analysis->error_code)->toBe('checksum_mismatch')
        ->and($analysis->error_context)->toMatchArray([
            'analysis_id' => $analysis->id,
            'demo_id' => $analysis->demo_id,
            'checksum_sha256' => $analysis->demo->checksum_sha256,
            'retryable' => false,
        ])
        ->and($analysis->demo->refresh()->status)->toBe(AnalysisStatus::Failed);
});

it('allows an owner to safely retry only a failed analysis', function () {
    Queue::fake();
    $user = User::factory()->create();
    $demo = Demo::factory()->for($user)->create(['status' => AnalysisStatus::Failed]);
    Analysis::factory()->for($demo)->failed()->create(['attempt' => 1]);

    $this->actingAs($user)
        ->postJson(route('api.demos.analysis.retry', $demo))
        ->assertAccepted()
        ->assertJsonPath('data.attempt', 2)
        ->assertJsonPath('data.status', AnalysisStatus::Queued->value);

    expect($demo->analyses()->count())->toBe(2);
    Queue::assertPushed(ProcessDemoAnalysis::class, 1);

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.demos.analysis.retry', $demo))
        ->assertForbidden();
});
