<?php

use App\Actions\ManageUserQuota;
use App\Actions\QuotaExceededException;
use App\Models\User;
use App\Models\UserQuota;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

it('limits imports using the configured daily allowance', function () {
    config()->set('quotas.daily_imports', 2);
    $user = User::factory()->create();
    $quotas = app(ManageUserQuota::class);

    $quotas->consumeImport($user);
    $quotas->consumeImport($user);

    expect(fn () => $quotas->consumeImport($user))
        ->toThrow(QuotaExceededException::class, 'The daily import limit of 2 has been reached.');

    expect($quotas->usage($user)['imports'])->toBe(['used' => 2, 'limit' => 2]);
});

it('resets import consumption on a new day', function () {
    config()->set('quotas.daily_imports', 1);
    Carbon::setTestNow('2026-08-11 10:00:00');
    $user = User::factory()->create();
    $quotas = app(ManageUserQuota::class);

    $quotas->consumeImport($user);
    Carbon::setTestNow('2026-08-12 10:00:00');
    $quotas->consumeImport($user);

    expect($quotas->usage($user)['imports'])->toBe(['used' => 1, 'limit' => 1]);
});

it('limits stored analyses and releases capacity', function () {
    config()->set('quotas.stored_analyses', 2);
    $user = User::factory()->create();
    $quotas = app(ManageUserQuota::class);

    $quotas->storeAnalysis($user);
    $quotas->storeAnalysis($user);

    expect(fn () => $quotas->storeAnalysis($user))
        ->toThrow(QuotaExceededException::class, 'The stored analysis limit of 2 has been reached.');

    $quotas->releaseAnalysis($user);
    $quotas->storeAnalysis($user);

    expect($quotas->usage($user)['analyses'])->toBe(['used' => 2, 'limit' => 2]);
});

it('never releases stored analysis consumption below zero', function () {
    $user = User::factory()->create();

    app(ManageUserQuota::class)->releaseAnalysis($user);

    expect(UserQuota::query()->whereBelongsTo($user)->value('stored_analyses'))->toBe(0);
});

it('shares current quota usage with the dashboard', function () {
    $user = User::factory()->create();
    UserQuota::factory()->for($user)->create([
        'daily_imports' => 3,
        'imports_on' => today(),
        'stored_analyses' => 12,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('quotas.imports', ['used' => 3, 'limit' => 5])
            ->where('quotas.analyses', ['used' => 12, 'limit' => 30])
        );
});
