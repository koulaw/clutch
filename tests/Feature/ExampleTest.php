<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the Clutch home page', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('home')
        );
});
