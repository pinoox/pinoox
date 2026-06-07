<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Package\AppProvisioner;
use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Logger;
use Pinoox\System\Model\Table;
use Pinoox\System\Model\UserModel;

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
            ]);

            throw new SetupException('install.err_provision');
        }

        $this->disableInstaller();
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

        if (!InstallerDatabase::testConnection($dbInput)) {
            return false;
        }

        if (!DatabaseCredentialsSync::persist($config)) {
            return false;
        }

        $this->reconnectDatabase();

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
        $appRoutes = Config::name('app')->get();
        AppRouter::setData($appRoutes);
        App::set('enable', false)->save();
    }

    private function reconnectDatabase(): void
    {
        $manager = DB::___();

        foreach (['default', 'platform'] as $connection) {
            try {
                $manager->getDatabaseManager()->purge($connection);
            } catch (\Throwable) {
            }
        }

        DB::register();
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
