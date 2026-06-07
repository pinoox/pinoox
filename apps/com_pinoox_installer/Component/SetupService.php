<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Migration\Migrator;
use Pinoox\Component\Package\AppProvisioner;
use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\System\Model\UserModel;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;

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
        if (!$this->installCore($dbInput, $userInput)) {
            throw new SetupException('install.err_insert_tables');
        }

        $appRoutes = Config::name('app')->get();
        AppRouter::setData($appRoutes);

        $this->provisionInstalledApps($lang);

        App::set('enable', false)->save();
    }

    /**
     * @param array<string, mixed> $dbInput
     * @param array<string, mixed> $userInput
     */
    private function installCore(array $dbInput, array $userInput): bool
    {
        if ($dbInput === [] || $userInput === []) {
            return false;
        }

        $config = InstallerDatabase::normalize($dbInput);

        if (!InstallerDatabase::testConnection($dbInput)) {
            return false;
        }

        if (!DatabaseCredentialsSync::persist($config)) {
            return false;
        }

        try {
            DB::register();

            (new Migrator('pincore', 'init'))->init();
            (new Migrator('pincore', 'run'))->run();
        } catch (\Throwable) {
            return false;
        }

        try {
            return (bool) UserModel::create([
                'app' => 'pincore',
                'fname' => $userInput['fname'],
                'lname' => $userInput['lname'],
                'username' => $userInput['username'],
                'password' => $userInput['password'],
                'email' => $userInput['email'],
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function provisionInstalledApps(?string $lang = null): void
    {
        (new AppProvisioner($this->engine))->provisionInstalledApps([
            'exclude' => [(string) App::package()],
            'lang' => $lang,
            'only_enabled' => true,
        ]);
    }

    public static function make(): self
    {
        return new self(AppEnginePortal::___());
    }
}
