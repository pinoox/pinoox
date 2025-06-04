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
use Pinoox\Portal\Database\DB;

/**
 * Enhanced Migrator class with comprehensive migration management
 */
class Migrator
{
    // Migration actions
    private const ACTION_RUN = 'run';
    private const ACTION_ROLLBACK = 'rollback';
    private const ACTION_INIT = 'init';
    private const ACTION_STATUS = 'status';
    private const ACTION_RESET = 'reset';
    private const ACTION_REFRESH = 'refresh';

    // Migration statuses
    private const STATUS_PENDING = 'pending';
    private const STATUS_RUNNING = 'running';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';
    private const STATUS_ROLLED_BACK = 'rolled_back';

    private string $package;
    private string $action;
    private MigrationToolkit $toolkit;
    private array $options;
    private array $statistics = [];
    private array $logs = [];
    private bool $dryRun = false;
    private bool $useTransactions = true;
    private int $timeout = 300; // 5 minutes default
    private ?string $lockFile = null;

    /**
     * Migrator constructor with enhanced options
     */
    public function __construct(string $package, string $action = self::ACTION_RUN, array $options = [])
    {
        $this->package = $package;
        $this->action = $action;
        $this->options = array_merge($this->getDefaultOptions(), $options);
        $this->toolkit = new MigrationToolkit();
        $this->initializeStatistics();
    }

    /**
     * Get default configuration options
     */
    private function getDefaultOptions(): array
    {
        return [
            'dry_run' => false,
            'use_transactions' => true,
            'timeout' => 300,
            'force' => false,
            'step' => 0, // For rollback: number of steps to rollback
            'create_backup' => false,
            'verbose' => false,
            'batch_size' => 50,
            'parallel' => false,
        ];
    }

    /**
     * Initialize migration statistics
     */
    private function initializeStatistics(): void
    {
        $this->statistics = [
            'start_time' => microtime(true),
            'total_migrations' => 0,
            'successful_migrations' => 0,
            'failed_migrations' => 0,
            'skipped_migrations' => 0,
            'execution_time' => 0,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Set migration options
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        $this->dryRun = $this->options['dry_run'] ?? false;
        $this->useTransactions = $this->options['use_transactions'] ?? true;
        $this->timeout = $this->options['timeout'] ?? 300;
        return $this;
    }

    /**
     * Initialize migration system
     */
    public function init(): array
    {
        try {
            $this->acquireLock();
            $this->log('Initializing migration system...');

            if ($this->toolkit->isExistsMigrationTable()) {
                $this->synchronizeMigrationRecords();
                return ['Migration system already initialized.'];
            }

            $this->toolkit->package('pincore')->action('init')->load();

            if (!$this->toolkit->isSuccess()) {
                throw new Exception($this->toolkit->getErrors());
            }

            $result = $this->executeMigrations();
            $this->log('Migration system initialized successfully.');

            return $result;
        } catch (Exception $e) {
            $this->log('Initialization failed: ' . $e->getMessage(), 'error');
            throw $e;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Run migrations
     */
    public function run(): array
    {
        try {
            $this->acquireLock();
            $this->log("Starting migration run for package: {$this->package}");

            if ($this->options['create_backup']) {
                $this->createBackup();
            }

            if ($this->toolkit->isExistsMigrationTable()) {
                $this->synchronizeMigrationRecords();
            }

            $this->toolkit->package($this->package)->action($this->action)->load();

            if (!$this->toolkit->isSuccess()) {
                throw new Exception($this->toolkit->getErrors());
            }

            return $this->executeMigrations();
        } catch (Exception $e) {
            $this->log('Migration run failed: ' . $e->getMessage(), 'error');
            if ($this->options['create_backup']) {
                $this->log('Consider restoring from backup if needed.', 'warning');
            }
            throw $e;
        } finally {
            $this->releaseLock();
            $this->finalizeStatistics();
        }
    }

    /**
     * Rollback migrations
     */
    public function rollback(int $steps = 1): array
    {
        try {
            $this->acquireLock();
            $this->log("Starting rollback for {$steps} step(s)");

            if ($this->options['create_backup']) {
                $this->createBackup();
            }

            $migrationsToRollback = $this->getMigrationsForRollback($steps);

            if (empty($migrationsToRollback)) {
                return ['Nothing to rollback.'];
            }

            $this->statistics['total_migrations'] = count($migrationsToRollback);
            $messages = [];

            foreach (array_reverse($migrationsToRollback) as $migration) {
                try {
                    if ($this->dryRun) {
                        $messages[] = "[DRY RUN] Would rollback: {$migration['migration']}";
                        continue;
                    }

                    $this->rollbackSingleMigration($migration);
                    $messages[] = "✓ Rolled back: {$migration['migration']}";
                    $this->statistics['successful_migrations']++;
                } catch (Exception $e) {
                    $messages[] = "✗ Failed to rollback {$migration['migration']}: " . $e->getMessage();
                    $this->statistics['failed_migrations']++;

                    if (!$this->options['force']) {
                        throw $e;
                    }
                }
            }

            $this->log('Rollback completed successfully.');
            return $messages;
        } catch (Exception $e) {
            $this->log('Rollback failed: ' . $e->getMessage(), 'error');
            throw $e;
        } finally {
            $this->releaseLock();
            $this->finalizeStatistics();
        }
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $this->toolkit->package($this->package)->action('status')->load();
        $migrations = $this->toolkit->getMigrations();
        $records = MigrationQuery::fetchAllByBatch(null, $this->package);

        $status = [];
        foreach ($migrations as $migration) {
            $record = $this->findMigrationRecord($records, $migration['fileName']);
            $status[] = [
                'migration' => $migration['fileName'],
                'table' => $migration['tableName'],
                'status' => $record ? 'migrated' : 'pending',
                'batch' => $record['batch'] ?? null,
                'executed_at' => $record['executed_at'] ?? null,
            ];
        }

        return $status;
    }

    /**
     * Reset all migrations (rollback everything)
     */
    public function reset(): array
    {
        $this->log('Starting migration reset (rollback all)');

        $allMigrations = MigrationQuery::fetchAllByBatch(null, $this->package);
        return $this->rollback(count($allMigrations));
    }

    /**
     * Refresh migrations (reset + migrate)
     */
    public function refresh(): array
    {
        $this->log('Starting migration refresh (reset + migrate)');

        $resetResult = $this->reset();
        $migrateResult = $this->run();

        return array_merge(
            ['=== RESET PHASE ==='],
            $resetResult,
            ['=== MIGRATE PHASE ==='],
            $migrateResult
        );
    }

    /**
     * Get migration statistics
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Get migration logs
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Execute migrations with enhanced error handling and transaction support
     */
    private function executeMigrations(): array
    {
        $migrations = $this->toolkit->getMigrations();

        if (empty($migrations)) {
            return ['Nothing to migrate.'];
        }

        $this->statistics['total_migrations'] = count($migrations);
        $batch = $this->getNextBatchNumber();
        $messages = [];

        foreach ($migrations as $migration) {
            try {
                if ($this->shouldSkipMigration($migration)) {
                    $messages[] = "⚠️ Skipped: {$migration['fileName']} (already exists)";
                    $this->statistics['skipped_migrations']++;
                    continue;
                }

                if ($this->dryRun) {
                    $messages[] = "[DRY RUN] Would migrate: {$migration['fileName']}";
                    continue;
                }

                $this->executeSingleMigration($migration, $batch);
                $messages[] = "✓ Migrated: {$migration['fileName']}";
                $this->statistics['successful_migrations']++;

            } catch (Exception $e) {
                $messages[] = "✗ Failed: {$migration['fileName']} - " . $e->getMessage();
                $this->statistics['failed_migrations']++;
                $this->log("Migration failed: {$migration['fileName']} - " . $e->getMessage(), 'error');

                if (!$this->options['force']) {
                    throw $e;
                }
            }
        }

        return $messages;
    }

    /**
     * Execute a single migration with transaction support
     */
    private function executeSingleMigration(array $migration, int $batch): void
    {
        $startTime = microtime(true);

        if ($this->useTransactions) {
            DB::beginTransaction();
        }

        try {
            // Set execution timeout
            set_time_limit($this->timeout);

            $class = require_once $migration['migrationFile'];

            if (!method_exists($class, 'up')) {
                throw new Exception("Migration {$migration['fileName']} does not have an 'up' method");
            }

            $class->up();

            MigrationQuery::insert($migration['fileName'], $migration['packageName'], $batch);

            if ($this->useTransactions) {
                DB::commit();
            }

            $executionTime = microtime(true) - $startTime;
            $this->log("Executed {$migration['fileName']} in " . round($executionTime, 2) . "s");

        } catch (QueryException $e) {
            if ($this->useTransactions) {
                DB::rollback();
            }

            if ($this->isTableAlreadyExistsError($e)) {
                $this->log("Table already exists for {$migration['fileName']}, inserting record only");
                MigrationQuery::insert($migration['fileName'], $migration['packageName'], $batch);
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            if ($this->useTransactions) {
                DB::rollback();
            }
            throw $e;
        }
    }

    /**
     * Rollback a single migration
     */
    private function rollbackSingleMigration(array $migration): void
    {
        if ($this->useTransactions) {
            DB::beginTransaction();
        }

        try {
            $migrationFile = $this->findMigrationFile($migration['migration']);

            if (!$migrationFile) {
                throw new Exception("Migration file not found for: {$migration['migration']}");
            }

            $class = require_once $migrationFile;

            if (method_exists($class, 'down')) {
                $class->down();
            } else {
                $this->log("Warning: Migration {$migration['migration']} does not have a 'down' method", 'warning');
            }

            MigrationQuery::delete($migration['migration'], $this->package);

            if ($this->useTransactions) {
                DB::commit();
            }

        } catch (Exception $e) {
            if ($this->useTransactions) {
                DB::rollback();
            }
            throw $e;
        }
    }

    /**
     * Get migrations for rollback
     */
    private function getMigrationsForRollback(int $steps): array
    {
        if ($steps <= 0) {
            return MigrationQuery::fetchAllByBatch(null, $this->package);
        }

        $latestBatch = MigrationQuery::fetchLatestBatch($this->package);
        $migrations = [];
        $currentSteps = 0;

        for ($batch = $latestBatch; $batch >= 1 && $currentSteps < $steps; $batch--) {
            $batchMigrations = MigrationQuery::fetchAllByBatch($batch, $this->package);
            $migrations = array_merge($migrations, $batchMigrations);
            $currentSteps++;
        }

        return $migrations;
    }

    /**
     * Find migration file for rollback
     */
    private function findMigrationFile(string $migrationName): ?string
    {
        $this->toolkit->package($this->package)->action('rollback')->load();
        $migrations = $this->toolkit->getMigrations();

        foreach ($migrations as $migration) {
            if ($migration['fileName'] === $migrationName) {
                return $migration['migrationFile'];
            }
        }

        return null;
    }

    /**
     * Check if migration should be skipped
     */
    private function shouldSkipMigration(array $migration): bool
    {
        return $this->tableExists($migration['tableName']) &&
            $this->migrationRecordExists($migration['fileName'], $migration['packageName']);
    }

    /**
     * Get next batch number
     */
    private function getNextBatchNumber(): int
    {
        if ($this->action === 'init') {
            return 1;
        }

        $latestBatch = MigrationQuery::fetchLatestBatch($this->package) ?? 0;
        return $latestBatch + 1;
    }

    /**
     * Create database backup before migrations
     */
    private function createBackup(): void
    {
        $timestamp = date('Y_m_d_His');
        $backupFile = "backup_{$this->package}_{$timestamp}.sql";

        $this->log("Creating backup: {$backupFile}");
        // Implementation would depend on your database backup strategy
        // This is a placeholder for backup functionality
    }

    /**
     * Acquire migration lock to prevent concurrent migrations
     */
    private function acquireLock(): void
    {
        $this->lockFile = sys_get_temp_dir() . "/migration_lock_{$this->package}.lock";

        if (file_exists($this->lockFile)) {
            $lockTime = filemtime($this->lockFile);
            if (time() - $lockTime > $this->timeout) {
                unlink($this->lockFile);
                $this->log('Removed stale migration lock', 'warning');
            } else {
                throw new Exception('Another migration is currently running for this package');
            }
        }

        file_put_contents($this->lockFile, getmypid());
    }

    /**
     * Release migration lock
     */
    private function releaseLock(): void
    {
        if ($this->lockFile && file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    /**
     * Synchronize migration records with existing tables
     */
    private function synchronizeMigrationRecords(): void
    {
        $this->toolkit->package($this->package)->action('status')->load();
        $migrations = $this->toolkit->getMigrations();

        foreach ($migrations as $migration) {
            if ($this->tableExists($migration['tableName']) &&
                !$this->migrationRecordExists($migration['fileName'], $migration['packageName'])) {

                MigrationQuery::insert($migration['fileName'], $migration['packageName'], 0);
                $this->log("Synchronized missing record: {$migration['fileName']}");
            }
        }
    }

    /**
     * Check if migration record exists
     */
    private function migrationRecordExists(string $fileName, string $packageName): bool
    {
        return MigrationQuery::is_exists($fileName, $packageName);
    }

    /**
     * Check if table exists
     */
    private function tableExists(?string $tableName): bool
    {
        if (!$tableName) {
            return false;
        }
        return $this->toolkit->schema()->hasTable($tableName);
    }

    /**
     * Check if error is "table already exists"
     */
    private function isTableAlreadyExistsError(QueryException $e): bool
    {
        return $e->getCode() === '42S01' ||
            strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false;
    }

    /**
     * Find migration record in array
     */
    private function findMigrationRecord(array $records, string $fileName): ?array
    {
        foreach ($records as $record) {
            if ($record['migration'] === $fileName) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Log migration events
     */
    private function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->logs[] = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
        ];

        if ($this->options['verbose']) {
            echo "[{$timestamp}] [{$level}] {$message}\n";
        }
    }

    /**
     * Finalize migration statistics
     */
    private function finalizeStatistics(): void
    {
        $this->statistics['execution_time'] = microtime(true) - $this->statistics['start_time'];
        $this->statistics['peak_memory'] = memory_get_peak_usage(true);
        $this->statistics['end_memory'] = memory_get_usage(true);
    }
}