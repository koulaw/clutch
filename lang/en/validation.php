<?php

return [
    'invitation' => 'This invitation is invalid or is no longer available.',
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'email' => 'The :attribute field must be a valid email address.',
    'in' => 'The selected :attribute is invalid.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'max' => ['string' => 'The :attribute field must not be greater than :max characters.'],
    'min' => ['string' => 'The :attribute field must be at least :min characters.'],
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'unique' => 'The :attribute has already been taken.',
    'password' => [
        'letters' => 'The password must contain at least one letter.',
        'mixed' => 'The password must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The password must contain at least one number.',
        'symbols' => 'The password must contain at least one symbol.',
        'uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
    ],
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'locale' => 'language',
    ],
];
