<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('renders the registration and login pages', function () {
    $this->get(route('register'))->assertSuccessful()->assertInertia(fn (Assert $page) => $page->component('auth/register'));
    $this->get(route('login'))->assertSuccessful()->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

it('registers a new user and requests email verification', function () {
    Notification::fake();
    $token = 'valid-invitation-token';
    $invitation = Invitation::factory()->create([
        'email' => 'remi@example.com',
        'token_hash' => hash('sha256', $token),
    ]);

    $response = $this->post(route('register'), [
        'name' => 'Rémi',
        'email' => 'remi@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'invitation' => $token,
    ]);

    $user = User::where('email', 'remi@example.com')->firstOrFail();

    $response->assertRedirectToRoute('verification.notice');
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
    expect($invitation->fresh())
        ->used_at->not->toBeNull()
        ->used_by_id->toBe($user->id);
});

it('authenticates and logs out a user', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirectToRoute('dashboard');

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirectToRoute('home');
    $this->assertGuest();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
