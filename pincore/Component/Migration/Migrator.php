<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */


namespace Pinoox\Component\Migration;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Portal\AppManager;
use Pinoox\Portal\MigrationToolkit;

/**
 * Class Migrator
 * @package Pinoox\Terminal\Migrate
 */
class Migrator
{
    /**
     * @var string The package name for migration.
     */
    private string $package;
    private string $action; //create, rollback, init

    /**
     * @var array The app information.
     */
    private array $app;

    /**
     * @var MigrationToolkit|null Migration toolkit instance.
     */
    private $toolkit = null;

    /**
     * Migrator constructor.
     * @param string $package The package name for migration.
     */
    public function __construct(string $package, string $action = 'run')
    {
        $this->package = $package;
        $this->action = $action;
    }

    /**
     * Initialize the migration process.
     * @throws \Exception When there's an error during the initialization process.
     */
    public function run(): string
    {
        try {
            $this->app = AppManager::getApp($this->package);
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $this->toolkit = MigrationToolkit::appPath($this->app['path'])
            ->migrationPath($this->app['migration'])
            ->package($this->app['package'])
            ->namespace($this->app['namespace'])
            ->action($this->action)
            ->load();

        if (!$this->toolkit->isSuccess()) {
            throw new \Exception($this->toolkit->getErrors());
        }

        return $this->migrate();
    }

    /**
     * Run the migration process.
     * @return string The status message after migration completion.
     */
    private function migrate(): string
    {
        $migrations = $this->toolkit->getMigrations();
        if (empty($migrations)) {
            return 'Nothing to migrate.';
        }

        $batch = 0;
        if ($this->action != 'init') {
            $batch = MigrationQuery::fetchLatestBatch($this->app['package']) ?? 0;
        }

        foreach ($migrations as $m) {
            $class = require_once $m['migrationFile'];
            $class->up();

            MigrationQuery::insert($m['fileName'], $m['packageName'], $batch);
        }

        return 'Migration completed successfully.';
    }
}