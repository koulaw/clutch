<?php

use App\AnalysisStatus;
use App\Models\Analysis;
use App\Models\Artifact;
use App\Models\Demo;
use App\Models\GameMatch;
use App\Models\GameRound;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('defines the complete analysis lifecycle in order', function () {
    expect(array_column(AnalysisStatus::cases(), 'value'))->toBe([
        'uploaded',
        'queued',
        'parsing',
        'analyzing',
        'ready',
        'failed',
        'unsupported',
    ]);

    expect(AnalysisStatus::Uploaded->canTransitionTo(AnalysisStatus::Queued))->toBeTrue()
        ->and(AnalysisStatus::Failed->canTransitionTo(AnalysisStatus::Queued))->toBeTrue()
        ->and(AnalysisStatus::Ready->canTransitionTo(AnalysisStatus::Queued))->toBeFalse()
        ->and(AnalysisStatus::Unsupported->canTransitionTo(AnalysisStatus::Queued))->toBeFalse();
});

it('casts every lifecycle state on demos and analyses', function (AnalysisStatus $status) {
    $demo = Demo::factory()->status($status)->create();
    $analysis = Analysis::factory()->for($demo)->status($status)->create();

    expect($demo->status)->toBe($status)
        ->and($analysis->status)->toBe($status);
})->with(AnalysisStatus::cases());

it('connects demos analyses matches rounds players and artifacts', function () {
    $user = User::factory()->create();
    $demo = Demo::factory()->for($user)->create();
    $analysis = Analysis::factory()->for($demo)->ready()->create();
    $gameMatch = GameMatch::factory()->for($analysis)->create();
    $gameRound = GameRound::factory()->for($gameMatch)->create();
    $players = Player::factory()->count(2)->create();

    $gameMatch->players()->attach($players->modelKeys(), [
        'team_name' => 'Clutch',
        'starting_side' => 'ct',
    ]);

    $artifact = Artifact::factory()
        ->for($analysis)
        ->for($gameRound)
        ->create();

    expect($user->demos()->sole()->is($demo))->toBeTrue()
        ->and($demo->analyses()->sole()->is($analysis))->toBeTrue()
        ->and($analysis->gameMatch()->sole()->is($gameMatch))->toBeTrue()
        ->and($gameMatch->rounds()->sole()->is($gameRound))->toBeTrue()
        ->and($gameMatch->players()->count())->toBe(2)
        ->and($players->first()->gameMatches()->sole()->is($gameMatch))->toBeTrue()
        ->and($analysis->artifacts()->sole()->is($artifact))->toBeTrue()
        ->and($gameRound->artifacts()->sole()->is($artifact))->toBeTrue();
});

it('prevents duplicate demo checksums for the same user', function () {
    $user = User::factory()->create();
    $checksum = str_repeat('a', 64);
    Demo::factory()->for($user)->create(['checksum_sha256' => $checksum]);

    expect(fn () => Demo::factory()->for($user)->create(['checksum_sha256' => $checksum]))
        ->toThrow(QueryException::class);

    $this->assertModelExists(Demo::factory()->create(['checksum_sha256' => $checksum]));
});

it('prevents duplicate analysis attempts and round numbers', function () {
    $demo = Demo::factory()->create();
    $analysis = Analysis::factory()->for($demo)->create(['attempt' => 1]);

    expect(fn () => Analysis::factory()->for($demo)->create(['attempt' => 1]))
        ->toThrow(QueryException::class);

    $gameMatch = GameMatch::factory()->for($analysis)->create();
    GameRound::factory()->for($gameMatch)->create(['number' => 1]);

    expect(fn () => GameRound::factory()->for($gameMatch)->create(['number' => 1]))
        ->toThrow(QueryException::class);
});

it('deletes owned analysis data while preserving reusable players', function () {
    $user = User::factory()->create();
    $demo = Demo::factory()->for($user)->create();
    $analysis = Analysis::factory()->for($demo)->create();
    $gameMatch = GameMatch::factory()->for($analysis)->create();
    $gameRound = GameRound::factory()->for($gameMatch)->create();
    $player = Player::factory()->create();
    $gameMatch->players()->attach($player);
    $artifact = Artifact::factory()->for($analysis)->for($gameRound)->create();

    $user->delete();

    $this->assertModelMissing($demo);
    $this->assertModelMissing($analysis);
    $this->assertModelMissing($gameMatch);
    $this->assertModelMissing($gameRound);
    $this->assertModelMissing($artifact);
    $this->assertModelExists($player);
});
