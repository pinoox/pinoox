<?php


return [
    'required' => 'The :attribute field is required.',
    'alpha_dash' => 'The :attribute must only contain letters, numbers, dashes and underscores.',
    'email' => 'The :attribute must be a valid email address.',
    'username_or_password_is_wrong' => 'invalid username or password',
    'same' => 'The :attribute and :other must match.',
    'different' => 'The :attribute and :other must be different.',
    'confirmed' => 'The :attribute confirmation does not match.',

    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],

    'attributes' => [
        'username' => 'username',
        "password" => "password",
        "email" => "email",
        "fname" => "first name",
        "lname" => "last name",
        'old_password' => 'current password',
        'new_password' => 'new password',
        'valid_password' => 'repeat new password',
    ],
];
