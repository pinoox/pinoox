<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package signature verification
    |--------------------------------------------------------------------------
    |
    | verify: when a .pinx package contains signature.json, validate it on install
    | require_signature: reject unsigned packages entirely
    |
    | trusted_keys: optional publisher public keys (base64) per package name
    |
    */
    'verify' => env('PINX_VERIFY', true),
    'require_signature' => env('PINX_REQUIRE_SIGNATURE', false),
    'keys_path' => env('PINX_KEYS_PATH', '~storage/pinx/keys'),

    'trusted_keys' => [
        // 'com_my_shop' => 'base64-ed25519-public-key',
    ],
];
