<?php



return [
    'profile' => 'hybrid',
    'stack' => 'vue',
    'entry' => 'src/main.js',
    'manifest' => 'dist/.vite/manifest.json',
    'mount' => '#app',
    'dev' => [
        'enabled' => (bool) _env('VITE_DEV', false),
        'port' => 5174,
        'url' => rtrim((string) _env('VITE_DEV_SERVER', 'http://127.0.0.1:5174'), '/'),
    ],
    'ssr' => [
        'enabled' => false,
        'mode' => 'hybrid',
    ],
    'seo' => [
        'defaults' => [
            'robots' => 'index,follow',
        ],
    ],
];

