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

use App\com_pinoox_installer\Controller\ApiController;
use function Pinoox\Router\{collect, get, post, routes};

return routes([
    'version' => 'v1',
    'prefix' => '',
    'docs' => [
        'url' => 'https://domain.com/installer',
        'markdown' => 'docs/api-extra.md',
    ],
    'routes' => collect(function () {
        get('/changeLang/{lang}', [ApiController::class, 'changeLang'])->name('changeLang');
        get('/agreement', [ApiController::class, 'agreement'])->name('agreement');
        post('/checkDB', [ApiController::class, 'checkDB'])->name('checkDB');
        get('/ping', [ApiController::class, 'ping'])->name('ping');
        get('/bootstrap/diagnostics', [ApiController::class, 'bootstrapDiagnostics'])->name('bootstrap.diagnostics');
        get('/htaccess/status', [ApiController::class, 'htaccessStatus'])->name('htaccess.status');
        post('/htaccess/create', [ApiController::class, 'htaccessCreate'])->name('htaccess.create');
        get('/checkPrerequisites', [ApiController::class, 'checkAllPrerequisites'])->name('prerequisites.all');
        get('/checkPrerequisites/{type}', [ApiController::class, 'checkPrerequisites'])->name('prerequisites.type');
        post('/setup', [ApiController::class, 'setup'])->name('setup');
    }),
]);

