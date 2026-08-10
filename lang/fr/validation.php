<?php

return [
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'in' => 'La valeur sélectionnée pour :attribute n’est pas valide.',
    'lowercase' => 'Le champ :attribute doit être en minuscules.',
    'max' => ['string' => 'Le champ :attribute ne doit pas dépasser :max caractères.'],
    'min' => ['string' => 'Le champ :attribute doit contenir au moins :min caractères.'],
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'Cette valeur pour :attribute est déjà utilisée.',
    'password' => [
        'letters' => 'Le mot de passe doit contenir au moins une lettre.',
        'mixed' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        'symbols' => 'Le mot de passe doit contenir au moins un symbole.',
        'uncompromised' => 'Ce mot de passe est apparu dans une fuite de données. Choisissez-en un autre.',
    ],
    'attributes' => [
        'name' => 'nom',
        'email' => 'adresse email',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'locale' => 'langue',
    ],
];
