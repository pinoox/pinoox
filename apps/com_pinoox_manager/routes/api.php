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

use Pinoox\Component\Router\RouteManifest;
use function Pinoox\Router\routes;

$private = RouteManifest::normalizeManifest(require __DIR__ . '/api/private.php');

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
    'routes' => array_merge(
        require __DIR__ . '/api/public.php',
        $private['routes'],
    ),
]);

