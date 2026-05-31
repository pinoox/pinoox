<?php

/**
 * Query-route rules for com_pinoox_installer.
 *
 * Short paths like /htaccess/status are mapped to router/api.php endpoints
 * under the /api/v1 collection (see router/routes.php).
 */

return [
    'path_aliases' => [
        '/pinoox.js' => '/dist/pinoox.js',
    ],
    'prefix_rules' => [
        [
            'prefix' => '/api/v1',
            'unless_starts_with' => ['/api/', '/dist/'],
        ],
    ],
];
