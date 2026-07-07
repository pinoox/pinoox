<?php



return [
    'profile' => 'hybrid',
    'stack' => 'vue',
    'entry' => 'src/main.js',
    'manifest' => 'dist/.vite/manifest.json',
    'mount' => '#app',
    'dev' => [
        'port' => 5174,
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

