<?php

use App\Models\Invitation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('creates an invitation with a hashed token', function () {
    $this->artisan('invitation:create', ['email' => ' BETA@Example.com ', '--days' => 5])
        ->expectsOutputToContain('created for beta@example.com')
        ->assertSuccessful();

    $invitation = Invitation::firstOrFail();

    expect($invitation)
        ->email->toBe('beta@example.com')
        ->token_hash->toHaveLength(64)
        ->expires_at->toDateString()->toBe(now()->addDays(5)->toDateString());
});

it('lists invitations and their statuses', function () {
    Invitation::factory()->create(['email' => 'available@example.com']);
    Invitation::factory()->expired()->create(['email' => 'expired@example.com']);
    Invitation::factory()->revoked()->create(['email' => 'revoked@example.com']);
    Invitation::factory()->used()->create(['email' => 'used@example.com']);

    $this->artisan('invitation:list')
        ->expectsTable(
            ['ID', 'Email', 'Status', 'Expires at', 'Created at'],
            Invitation::query()->latest()->get()->map(fn (Invitation $invitation): array => [
                $invitation->id,
                $invitation->email,
                match ($invitation->email) {
                    'expired@example.com' => 'Expired',
                    'revoked@example.com' => 'Revoked',
                    'used@example.com' => 'Used',
                    default => 'Available',
                },
                $invitation->expires_at?->toDateTimeString() ?? 'Never',
                $invitation->created_at->toDateTimeString(),
            ])->all(),
        )
        ->assertSuccessful();
});

it('revokes an available invitation', function () {
    $invitation = Invitation::factory()->create();

    $this->artisan('invitation:revoke', ['invitation' => $invitation->id])
        ->expectsOutputToContain('revoked')
        ->assertSuccessful();

    expect($invitation->fresh()->revoked_at)->not->toBeNull();
});
