<?php

use App\com_pinoox_installer\Component\InstallerDatabase;
use App\com_pinoox_installer\Component\PrerequisitesChecker;

it('reports available database drivers in prerequisites check', function () {
    $result = (new PrerequisitesChecker())->check('database');

    expect($result)->toHaveKeys(['state', 'available', 'connections'])
        ->and($result['connections'])->toHaveKeys(InstallerDatabase::INSTALLABLE_CONNECTIONS);

    foreach (InstallerDatabase::INSTALLABLE_CONNECTIONS as $name) {
        $expected = InstallerDatabase::extensionStatus($name)['available'];

        expect($result['connections'][$name]['state'])
            ->toBe($expected ? 'pass' : 'fail');
    }

    if (InstallerDatabase::availableConnections() !== []) {
        expect($result['state'])->toBe('pass')
            ->and($result['available'])->not->toBeEmpty();
    }
});

it('keeps mysql as alias for database prerequisite type', function () {
    $database = (new PrerequisitesChecker())->check('database');
    $mysqlAlias = (new PrerequisitesChecker())->check('mysql');

    expect($mysqlAlias['state'])->toBe($database['state'])
        ->and($mysqlAlias['available'])->toBe($database['available']);
});

it('lists installable connections from extension probe', function () {
    $available = InstallerDatabase::availableConnections();

    foreach ($available as $connection) {
        expect(InstallerDatabase::extensionStatus($connection)['available'])->toBeTrue();
    }
});
