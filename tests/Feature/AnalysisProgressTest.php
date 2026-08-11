<?php

use App\AnalysisStatus;
use App\Events\AnalysisProgressUpdated;
use App\Models\Analysis;
use App\Models\Demo;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

it('exposes only the signed-in users latest analysis attempts for polling', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $demo = Demo::factory()->for($user)->create();
    Analysis::factory()->for($demo)->create(['attempt' => 1, 'status' => AnalysisStatus::Failed]);
    $latest = Analysis::factory()->for($demo)->create(['attempt' => 2, 'status' => AnalysisStatus::Analyzing]);
    Analysis::factory()->for(Demo::factory()->for($otherUser))->create();

    $response = $this->actingAs($user)->getJson(route('api.analyses.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $latest->id)
        ->assertJsonPath('data.0.demo_id', $demo->public_id)
        ->assertJsonPath('data.0.attempt', 2)
        ->assertJsonPath('data.0.status', 'analyzing')
        ->assertJsonPath('data.0.progress', 75)
        ->assertJsonPath('data.0.is_terminal', false)
        ->assertJsonPath('data.0.can_retry', false)
        ->assertJsonPath('data.0.error', null);

    $response->assertJsonMissing(['demo_id' => $otherUser->demos()->sole()->public_id]);
});

it('returns a safe retryable error without exposing worker internals', function () {
    $user = User::factory()->create();
    $analysis = Analysis::factory()->for(Demo::factory()->for($user))->failed()->create([
        'error_message' => 'Internal path /private/worker/demo.dem failed.',
    ]);

    $this->actingAs($user)->getJson(route('api.analyses.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $analysis->id)
        ->assertJsonPath('data.0.progress', 100)
        ->assertJsonPath('data.0.can_retry', true)
        ->assertJsonPath('data.0.error.code', 'parser_failed')
        ->assertJsonPath('data.0.error.retryable', true)
        ->assertJsonPath('data.0.error.message', 'The analysis could not be completed. You can try again.')
        ->assertJsonMissing(['message' => 'Internal path /private/worker/demo.dem failed.']);
});

it('allows an unsupported analysis to be retried after support is added', function () {
    $user = User::factory()->create();
    Analysis::factory()->for(Demo::factory()->for($user))->create([
        'status' => AnalysisStatus::Unsupported,
        'error_code' => 'unsupported_demo',
    ]);

    $this->actingAs($user)->getJson(route('api.analyses.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.status', 'unsupported')
        ->assertJsonPath('data.0.can_retry', true)
        ->assertJsonPath('data.0.error.retryable', true);
});

it('broadcasts status changes on the owners private channel', function () {
    $analysis = Analysis::factory()->create();
    Event::fake([AnalysisProgressUpdated::class]);

    $analysis->update(['status' => AnalysisStatus::Parsing]);

    Event::assertDispatched(AnalysisProgressUpdated::class, function (AnalysisProgressUpdated $event) use ($analysis): bool {
        $channel = $event->broadcastOn();

        return $channel instanceof PrivateChannel
            && $channel->name === 'private-users.'.$analysis->demo->user_id.'.analyses'
            && $event->broadcastAs() === 'analysis.progress.updated'
            && $event->broadcastWith()['analysis']['status'] === 'parsing'
            && $event->broadcastWith()['analysis']['progress'] === 35;
    });
});

it('authorizes only the owner to subscribe to an analysis channel', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $authorization = Broadcast::getChannels()['users.{userId}.analyses'];

    expect($authorization($owner, $owner->id))->toBeTrue()
        ->and($authorization($otherUser, $owner->id))->toBeFalse();
});

it('requires authentication to poll analysis progress', function () {
    $this->getJson(route('api.analyses.index'))->assertUnauthorized();
});
