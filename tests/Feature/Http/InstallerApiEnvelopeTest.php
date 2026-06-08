<?php

use App\com_pinoox_installer\Controller\ApiController;
use Pinoox\Portal\App\App;

it('returns installer ping in the standard envelope', function () {
    pinooxBoot();

    inApp('com_pinoox_installer', function () {
        $response = (new ApiController())->ping();
        $payload = json_decode($response->getContent(), true);

        expect($payload['success'])->toBeTrue()
            ->and($payload['data'])->toHaveKeys(['ok', 'routing', 'timestamp'])
            ->and($payload['data']['ok'])->toBeTrue();
    });
});

it('returns installer db check errors in the standard envelope', function () {
    pinooxBoot();

    inApp('com_pinoox_installer', function () {
        $request = \Pinoox\Component\Http\Request::create('/', 'POST', [
            'host' => '127.0.0.1',
            'database' => 'missing_db_' . uniqid(),
            'username' => 'root',
            'password' => 'invalid-password',
        ]);

        $response = (new ApiController())->checkDB($request);
        $payload = json_decode($response->getContent(), true);

        expect($payload['success'])->toBeFalse()
            ->and($payload['error']['code'])->toBe('DB_CONNECTION_FAILED')
            ->and($payload['error']['message'])->toBe(t('install.err_connect_to_database'));
    });
});

it('exposes translated installer db messages in lang files', function () {
    pinooxBoot();

    inApp('com_pinoox_installer', function () {
        expect(t('install.connect_to_database'))->not->toBe('install.connect_to_database')
            ->and(t('install.err_connect_to_database'))->not->toBe('install.err_connect_to_database')
            ->and(t('install.setup_success'))->not->toBe('install.setup_success');
    });
});

