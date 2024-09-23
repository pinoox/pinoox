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

namespace Pinoox\Component\Migration;

use Exception;
use Illuminate\Database\QueryException;

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
    private string $action; // create, rollback, init

    private MigrationToolkit $mig;

    /**
     * Migrator constructor.
     * @param string $package The package name for migration.
     * @param string $action  The migration action to be performed (default is 'run').
     */
    public function __construct(string $package, string $action = 'run')
    {
        $this->package = $package;
        $this->action = $action;
    }

    /**
     * @throws Exception
     */
    public function init(): array|bool
    {
        $this->mig = new MigrationToolkit();

        // Check if the migration table exists
        if ($this->mig->isExistsMigrationTable()) {
            // Synchronize migration records with existing tables
            $this->synchronizeMigrationRecords();
            return false;
        }

        // Initialize the migration table if it doesn't exist
        $this->mig->package('pincore')->action('init')->load();

        if (!$this->mig->isSuccess()) {
            throw new Exception($this->mig->getErrors());
        }

        return $this->migrate();
    }

    /**
     * Run the migration process.
     * @throws Exception When there's an error during the migration process.
     */
    public function run(): array
    {
        $this->mig = new MigrationToolkit();

        // Check if the migration table exists and synchronize records if necessary
        if ($this->mig->isExistsMigrationTable()) {
            $this->synchronizeMigrationRecords();
        }

        // Load the migration files and run them
        $this->mig->package($this->package)->action($this->action)->load();

        if (!$this->mig->isSuccess()) {
            throw new Exception($this->mig->getErrors());
        }

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
            if ($this->tableExists($m['tableName']) && $this->migrationRecordExists($m['fileName'], $m['packageName'])) {
                $messages[] = '⚠️ [' . $m['fileName'] . '] skipped (table already exists and record present)';
                continue;
            }

            try {
                $class = require_once $m['migrationFile'];
                $class->up();

                MigrationQuery::insert($m['fileName'], $m['packageName'], $batch);
                $messages[] = '✓ [' . $m['fileName'] . '] migrated successfully';
            } catch (QueryException $e) {
                if ($this->isTableAlreadyExistsError($e)) {
                    $messages[] = '⚠️ [' . $m['fileName'] . '] skipped (table already exists)';
                    MigrationQuery::insert($m['fileName'], $m['packageName'], $batch); // Insert migration record
                } else {
                    throw $e; // Re-throw the exception if it's not a table already exists error
                }
            }
        }

        return $messages;
    }

    /**
     * Synchronize the migration table records with existing tables in the database.
     */
    private function synchronizeMigrationRecords(): void
    {
        $migrations = $this->mig->getMigrations();

        foreach ($migrations as $m) {
            if ($this->tableExists($m['tableName']) && !$this->migrationRecordExists($m['fileName'], $m['packageName'])) {
                // Insert the migration record if the table exists but the record does not
                MigrationQuery::insert($m['fileName'], $m['packageName'], 0);
                echo "Inserted missing migration record for [{$m['fileName']}].\n";
            }
        }
    }

    /**
     * Check if the migration record exists in the migration table.
     * @param string $fileName
     * @param string $packageName
     * @return bool
     */
    private function migrationRecordExists(string $fileName, string $packageName): bool
    {
        return MigrationQuery::is_exists($fileName, $packageName);
    }

    /**
     * Check if the table exists in the database.
     * @param string $tableName
     * @return bool
     */
    private function tableExists(string $tableName): bool
    {
        return $this->mig->schema()->hasTable($tableName);
    }

    /**
     * Determine if the given exception is a "table already exists" error.
     * @param QueryException $e
     * @return bool
     */
    private function isTableAlreadyExistsError(QueryException $e): bool
    {
        // MySQL error code 1050 corresponds to "Table already exists"
        return $e->getCode() === '42S01' || strpos($e->getMessage(), 'already exists') !== false;
    }
}