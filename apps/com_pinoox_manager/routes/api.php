<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

use function Pinoox\Router\route_files;
use function Pinoox\Router\routes;

return routes([
    'version' => 'v1',
    'prefix' => '',
    'docs' => [
        'title' => 'Manager API',
        'description' => 'REST endpoints for the Pinoox manager panel, authentication, apps, routing, updates, and user settings.',
        'audience' => 'external',
        'path' => 'docs/api',
        'internal_path' => 'docs/api-internal',
    ],
    'routes' => route_files('api'),
]);
