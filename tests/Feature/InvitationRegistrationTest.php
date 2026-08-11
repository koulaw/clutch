<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(LazilyRefreshDatabase::class);

it('prefills the invitation from the registration URL', function () {
    $this->get(route('register', ['invitation' => 'plain-token']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/register')
            ->where('invitation', 'plain-token')
        );
});

it('rejects registration without a valid invitation', function (?string $token) {
    registerWithInvitation($this, $token)
        ->assertSessionHasErrors('invitation');

    expect(User::query()->count())->toBe(0);
})->with([
    'missing' => null,
    'unknown' => 'unknown-token',
]);

it('rejects unavailable invitations', function (string $state) {
    $token = "{$state}-token";
    Invitation::factory()->{$state}()->create([
        'email' => 'invited@example.com',
        'token_hash' => hash('sha256', $token),
    ]);

    registerWithInvitation($this, $token)
        ->assertSessionHasErrors('invitation');

    expect(User::query()->count())->toBe(0);
})->with(['expired', 'revoked', 'used']);

it('rejects an invitation issued to another email address', function () {
    $token = 'email-bound-token';
    Invitation::factory()->create([
        'email' => 'someone-else@example.com',
        'token_hash' => hash('sha256', $token),
    ]);

    registerWithInvitation($this, $token)
        ->assertSessionHasErrors('invitation');
});

it('consumes an invitation only once', function () {
    $token = 'single-use-token';
    Invitation::factory()->create([
        'email' => 'invited@example.com',
        'token_hash' => hash('sha256', $token),
    ]);

    registerWithInvitation($this, $token)->assertRedirectToRoute('verification.notice');
    $this->post(route('logout'));
    registerWithInvitation($this, $token)->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'invited@example.com')->count())->toBe(1);
});

function registerWithInvitation(TestCase $testCase, ?string $token): TestResponse
{
    return $testCase->post(route('register'), [
        'name' => 'Invited User',
        'email' => 'invited@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'invitation' => $token,
    ]);
}
