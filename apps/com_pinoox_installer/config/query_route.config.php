<?php

/**
 * Query-route rules for com_pinoox_installer.
 *
 * Short paths like /htaccess/status are mapped to routes/api.php endpoints
 * under routes/api.php.
 */

return [
    'path_aliases' => [],
    'prefix_rules' => [
        [
            'prefix' => '/api/v1',
            'unless_starts_with' => ['/api/', '/dist/'],
        ],
    ],
];

