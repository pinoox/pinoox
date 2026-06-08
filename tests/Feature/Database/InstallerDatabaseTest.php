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

use App\com_pinoox_installer\Component\InstallerDatabase;
use App\com_pinoox_installer\Request\SetupRequest;
use Pinoox\Portal\Validation;

it('reads nested db payload from setup requests', function () {
    $request = appRequest('POST', '/setup', json: [
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
        ],
        'user' => ['username' => 'admin'],
    ]);

    expect(InstallerDatabase::readFromRequest($request))->toMatchArray([
        'host' => '127.0.0.1',
        'database' => 'pinoox',
        'username' => 'root',
        'password' => 'secret',
        'prefix' => 'pinx_',
    ]);
});

it('reads flat db payload from checkDB requests', function () {
    $request = appRequest('POST', '/checkDB', [
        'host' => 'localhost',
        'database' => 'pin',
        'username' => 'root',
        'password' => '',
        'prefix' => 'pinx_',
    ]);

    expect(InstallerDatabase::readFromRequest($request))->toMatchArray([
        'host' => 'localhost',
        'database' => 'pin',
        'username' => 'root',
        'password' => '',
        'prefix' => 'pinx_',
    ]);
});

it('merges validated setup db data with the raw request payload', function () {
    $request = appRequest('POST', '/setup', json: [
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
        ],
    ]);

    $merged = InstallerDatabase::readForSetup($request, [
        'host' => '127.0.0.1',
        'database' => 'pinoox',
        'username' => 'root',
    ]);

    expect($merged['password'])->toBe('secret')
        ->and($merged['prefix'])->toBe('pinx_')
        ->and($merged['driver'])->toBe('mysql');
});

it('accepts SetupRequest in readForSetup', function () {
    $httpRequest = appRequest('POST', '/setup', json: [
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
        ],
        'user' => [
            'fname' => 'Ada',
            'lname' => 'Lovelace',
            'email' => 'ada@example.com',
            'username' => 'ada',
            'password' => 'secret123',
        ],
    ]);
    $httpRequest->setValidation(Validation::___());

    $setupRequest = new SetupRequest($httpRequest);
    $setupRequest->__resolve();

    $merged = InstallerDatabase::readForSetup($setupRequest, $setupRequest->validated('db'));

    expect($merged['password'])->toBe('secret')
        ->and($merged['database'])->toBe('pinoox');
});

it('keeps selected connection name in readForSetup output', function () {
    $request = appRequest('POST', '/setup', json: [
        'db' => [
            'connection' => 'mariadb',
            'host' => 'db.local',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'port' => '3307',
        ],
    ]);

    $merged = InstallerDatabase::readForSetup($request, [
        'connection' => 'mariadb',
        'host' => 'db.local',
        'database' => 'pinoox',
        'username' => 'root',
    ]);

    expect($merged['connection'])->toBe('mariadb')
        ->and($merged['host'])->toBe('db.local')
        ->and($merged['port'])->toBe('3307')
        ->and(InstallerDatabase::connectionName($merged))->toBe('mariadb');
});
