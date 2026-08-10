<?php

use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

it('keeps the French and English catalogues in sync', function () {
    $frenchKeys = array_keys(Arr::dot(require lang_path('fr/ui.php')));
    $englishKeys = array_keys(Arr::dot(require lang_path('en/ui.php')));

    expect($frenchKeys)->toBe($englishKeys);
});

it('shares the English translation catalogue', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('translations.home.headline_primary', 'Review the round.')
        );
});

it('switches to the French translation catalogue', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('locale', 'fr');

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'fr')
            ->where('translations.home.headline_primary', 'Revoyez le round.')
        );
});

it('rejects unsupported locales', function () {
    $this->post(route('locale.update'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale');
});

it('localizes validation errors in French', function () {
    $this->withSession(['locale' => 'fr'])
        ->post(route('register'), [])
        ->assertSessionHasErrors([
            'name' => 'Le champ nom est obligatoire.',
            'email' => 'Le champ adresse email est obligatoire.',
            'password' => 'Le champ mot de passe est obligatoire.',
        ]);
});
