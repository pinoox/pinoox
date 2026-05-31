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

use function Pinoox\Router\{get,post};

get(
    path: '/changeLang/{lang}',
    action: 'changeLang',
);

get(
    path: '/agreement',
    action: 'agreement',
);

post(
    path: '/checkDB',
    action: 'checkDB',
);

get(
    path: '/ping',
    action: 'ping',
);

get(
    path: '/bootstrap/diagnostics',
    action: 'bootstrapDiagnostics',
);

get(
    path: '/htaccess/status',
    action: 'htaccessStatus',
);

post(
    path: '/htaccess/create',
    action: 'htaccessCreate',
);

get(
    path: '/checkPrerequisites',
    action: 'checkAllPrerequisites',
);

get(
    path: '/checkPrerequisites/{type}',
    action: 'checkPrerequisites',
);

post(
    path: '/setup',
    action: 'setup',
);