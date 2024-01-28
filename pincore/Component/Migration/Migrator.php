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

    private MigrationToolkit $mig;


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
     * @throws \Exception
     */
    public function init(): array|bool
    {
        $this->mig = new MigrationToolkit();

        if ($this->mig->isExistsMigrationTable()) return false;
        $this->mig->package('pincore')->action('init')->load();

        if (!$this->mig->isSuccess()) throw new \Exception($this->mig->getErrors());
        return $this->migrate();
    }

    /**
     * Initialize the migration process.
     * @throws \Exception When there's an error during the initialization process.
     */
    public function run(): array
    {
        $this->mig = new MigrationToolkit();
        $this->mig->package($this->package)->action($this->action)->load();
        if (!$this->mig->isSuccess()) throw new \Exception($this->mig->getErrors());

        return $this->migrate();
    }

    /**
     * Run the migration process.
     * @return array The status message after migration completion.
     */
    private function migrate(): array
    {
        $migrations = $this->mig->getMigrations();

        if (empty($migrations)) {
            return ['Nothing to migrate.'];
        }

        $batch = 0;
        if ($this->action != 'init') {
            $batch = MigrationQuery::fetchLatestBatch($this->package) ?? 0;
        }

        $messages = [];
        foreach ($migrations as $m) {
            $class = require_once $m['migrationFile'];
            $class->up();

            MigrationQuery::insert($m['fileName'], $m['packageName'], $batch);

            $messages[] = '✓ [' . $m['fileName'] . '] migrated successfully';
        }

        return $messages;
    }
}