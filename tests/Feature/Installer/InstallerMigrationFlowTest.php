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
use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Migration\Migrator;
use Pinoox\Portal\Database\DB;
use Pinoox\Model\Table;
use Pinoox\Model\UserModel;

it('normalizes installer database input with pinx default prefix', function () {
    $config = InstallerDatabase::normalize([
        'host' => '127.0.0.1',
        'database' => 'pin',
        'username' => 'root',
        'password' => 'secret',
    ]);

    expect($config)
        ->toMatchArray([
            'host' => '127.0.0.1',
            'database' => 'pin',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
            'driver' => 'mysql',
            'port' => '3306',
        ]);
});

it('documents installer integration opt-in flag', function () {
    expect(installerIntegrationEnabled())->toBeBool();
});

it('re-runs missing user table when history records exist on development prefix', function () {
    $base = installerIntegrationDatabaseConfig();

    if ($base === null) {
        test()->markTestSkipped('Set PINOOX_INSTALLER_INTEGRATION=1 and configure MySQL development DB to run installer integration tests.');
    }

    $config = array_merge($base, [
        'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
    ]);

    installerRegisterDatabase($config);

    if (!installerPhysicalTableExists(Table::USER, $config)) {
        (new Migrator('platform', 'run'))->run();
    }

    expect(installerPhysicalTableExists(Table::USER, $config))->toBeTrue();

    $userTable = DB::physicalTableName(Table::USER, 'platform');
    $connection = DB::connection('platform');
    $connection->statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        $connection->statement('DROP TABLE IF EXISTS `' . str_replace('`', '``', $userTable) . '`');
    } finally {
        $connection->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    (new Migrator('platform', 'run'))->run();

    expect(installerPhysicalTableExists(Table::USER, $config))->toBeTrue();
});

it('runs core migrate and system patches through AppProvisioner during setup', function () {
    $base = installerIntegrationDatabaseConfig();

    if ($base === null) {
        test()->markTestSkipped('Set PINOOX_INSTALLER_INTEGRATION=1 and configure MySQL development DB to run installer integration tests.');
    }

    $config = array_merge($base, [
        'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
    ]);

    installerRegisterDatabase($config);

    (new \Pinoox\Component\Package\AppProvisioner(\Pinoox\Portal\App\AppEngine::___()))->provisionCore();

    expect(installerPhysicalTableExists(Table::HISTORY, $config))->toBeTrue()
        ->and(installerPhysicalTableExists(Table::USER, $config))->toBeTrue();

    $patchHistory = \Pinoox\Model\HistoryModel::where('type', \Pinoox\Component\Migration\MigrationQuery::TYPE_PATCH)
        ->where('app', 'platform')
        ->where('migration', '2026_06_06_085158_test')
        ->where('status', 'success')
        ->exists();

    expect($patchHistory)->toBeTrue();
});

it('creates admin user when core tables already exist', function () {
    $base = installerIntegrationDatabaseConfig();

    if ($base === null) {
        test()->markTestSkipped('Set PINOOX_INSTALLER_INTEGRATION=1 and configure MySQL development DB to run installer integration tests.');
    }

    $config = array_merge($base, [
        'prefix' => DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
    ]);

    installerRegisterDatabase($config);

    if (!installerPhysicalTableExists(Table::USER, $config)) {
        (new Migrator('platform', 'run'))->run();
    }

    expect(installerPhysicalTableExists(Table::USER, $config))->toBeTrue();

    $service = \App\com_pinoox_installer\Component\SetupService::make();
    $ensureAdmin = new ReflectionMethod($service, 'ensureAdminUser');
    $ensureAdmin->setAccessible(true);

    $username = 'installer_admin_' . uniqid();

    expect($ensureAdmin->invoke($service, [
        'fname' => 'Test',
        'lname' => 'Admin',
        'username' => $username,
        'password' => 'secret123',
        'email' => $username . '@example.test',
    ]))->toBeTrue();

    expect(
        UserModel::withoutGlobalScopes()
            ->where('app', 'platform')
            ->where('username', $username)
            ->exists(),
    )->toBeTrue();
});

