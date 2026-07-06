<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Package\AppProvisioner;
use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Logger;
use Pinoox\Model\Table;
use Pinoox\Model\UserModel;
use Pinoox\Support\SystemConfig;

final class SetupService
{
    public function __construct(
        private readonly AppEngine $engine,
    ) {
    }

    /**
     * @param array<string, mixed> $dbInput
     * @param array<string, mixed> $userInput
     */
    public function run(array $dbInput, array $userInput, ?string $lang = null): void
    {
        @set_time_limit(600);
        ignore_user_abort(true);

        if (!$this->prepareDatabase($dbInput)) {
            throw new SetupException('install.err_insert_tables');
        }

        try {
            $this->migrateTables();
            $this->runPatches();

            if (!$this->ensureAdminUser($userInput)) {
                throw new SetupException('install.err_insert_tables');
            }

            $this->configureApps($lang);
        } catch (SetupException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Logger::error('Installer setup failed: ' . $e->getMessage(), [
                'exception' => $e,
                'migration_path' => SystemConfig::platformPath('migrations'),
                'patch_path' => SystemConfig::platformPath('patches'),
            ]);

            throw new SetupException('install.err_provision');
        }

        try {
            $this->disableInstaller();
        } catch (\Throwable $e) {
            Logger::error('Installer disable step failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw new SetupException('install.err_provision');
        }
    }

    /**
     * @param array<string, mixed> $dbInput
     */
    private function prepareDatabase(array $dbInput): bool
    {
        if ($dbInput === []) {
            return false;
        }

        $config = InstallerDatabase::normalize($dbInput);
        $connectionName = InstallerDatabase::connectionName($dbInput);

        if (!InstallerDatabase::testConnection($dbInput)) {
            return false;
        }

        if (!DatabaseCredentialsSync::persist($config, $connectionName)) {
            return false;
        }

        $this->applyInstallConnection($connectionName);
        $this->reconnectDatabase($config);

        return true;
    }

    private function migrateTables(): void
    {
        $this->provisioner()->provisionCore(['skip_patch' => true]);

        if (!$this->coreTablesReady()) {
            throw new SetupException('install.err_insert_tables');
        }

        $this->provisioner()->migratePackages($this->projectPackages());
    }

    private function runPatches(): void
    {
        $this->provisioner()->provisionCore(['skip_migrate' => true]);
        $this->provisioner()->patchPackages($this->projectPackages());
    }

    private function configureApps(?string $lang): void
    {
        $this->provisioner()->applyLangToPackages($this->projectPackages(), $lang);
    }

    /**
     * @return list<string>
     */
    private function projectPackages(): array
    {
        return $this->provisioner()->packagesForSetup([
            'exclude' => [(string) App::package()],
            'only_enabled' => true,
        ]);
    }

    private function disableInstaller(): void
    {
        $routesFile = AppEnginePortal::path('com_pinoox_installer') . '/config/app.config.php';
        $postInstallRoutes = is_file($routesFile) ? require $routesFile : [];

        AppRouter::setData(is_array($postInstallRoutes) ? $postInstallRoutes : []);
        App::set('enable', false)->save();
    }

    private function reconnectDatabase(array $config): void
    {
        SystemConfig::clearCache();
        DB::refreshCoreConnection($config);
    }

    private function applyInstallConnection(string $connectionName): void
    {
        foreach (['DB_CONNECTION'] as $key) {
            $_ENV[$key] = $connectionName;
            $_SERVER[$key] = $connectionName;
            putenv($key . '=' . $connectionName);
        }

        SystemConfig::clearCache();
    }

    private function coreTablesReady(): bool
    {
        foreach ([Table::HISTORY, Table::USER, Table::TOKEN, Table::FILE, Table::ROLE] as $table) {
            try {
                $physical = DB::physicalTableName($table, 'platform');
                $connection = DB::connection('platform');
                $database = (string) $connection->getDatabaseName();

                if ($database === '' || $physical === '') {
                    return false;
                }

                $row = $connection->selectOne(
                    'SELECT 1 AS found FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1',
                    [$database, $physical],
                );

                if ($row === null) {
                    Logger::error('Installer missing core table after migration: ' . $physical);

                    return false;
                }
            } catch (\Throwable $e) {
                Logger::error('Installer core table check failed: ' . $e->getMessage(), ['exception' => $e]);

                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $userInput
     */
    private function ensureAdminUser(array $userInput): bool
    {
        try {
            $username = (string) ($userInput['username'] ?? '');

            if ($username === '') {
                return false;
            }

            $exists = UserModel::withoutGlobalScopes()
                ->where('app', 'platform')
                ->where('username', $username)
                ->exists();

            if ($exists) {
                return true;
            }

            return (bool) UserModel::withoutGlobalScopes()->create([
                'app' => 'platform',
                'group_key' => 'admin',
                'fname' => $userInput['fname'],
                'lname' => $userInput['lname'],
                'username' => $username,
                'password' => $userInput['password'],
                'email' => $userInput['email'],
            ]);
        } catch (\Throwable $e) {
            Logger::error('Installer admin user creation failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }

    private function provisioner(): AppProvisioner
    {
        return new AppProvisioner($this->engine);
    }

    public static function make(): self
    {
        return new self(AppEnginePortal::___());
    }
}
