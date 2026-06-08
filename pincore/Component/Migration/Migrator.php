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
use Pinoox\Portal\Logger;
use Pinoox\Model\HistoryModel;
use Pinoox\Model\Table;

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
            if ($this->package !== 'platform') {
                return (new self('platform', self::ACTION_INIT, $this->options))->init();
            }

            $this->log('Initializing migration system...');

            if ($this->toolkit->isExistsMigrationTable()) {
                return ['Migration system already initialized.'];
            }

            $this->toolkit->package('platform')->action('init')->load();

            if (!$this->toolkit->isSuccess()) {
                throw new Exception($this->toolkit->getErrors());
            }

            $result = $this->executeMigrations();
            MigrationQuery::importLegacyMigrationRecords();

            if (!$this->toolkit->isExistsMigrationTable()) {
                throw new Exception('Migration history table was not created during initialization.');
            }

            $this->log('Migration system initialized successfully.');

            return $result;
        } catch (Exception $e) {
            $this->log('Initialization failed: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Run migrations
     */
    public function run(): array
    {
        try {
            if ($this->package === 'platform') {
                if (!$this->toolkit->isExistsMigrationTable()) {
                    $this->log("Migration table doesn't exist, initializing...");
                    $this->init();

                    if (!$this->toolkit->isExistsMigrationTable()) {
                        throw new Exception('Migration history table is missing after initialization.');
                    }

                    $this->toolkit = new MigrationToolkit();
                }

                $this->toolkit->package($this->package)->action($this->action)->load();
                if (!$this->toolkit->isSuccess()) {
                    throw new Exception($this->toolkit->getErrors());
                }

                MigrationQuery::importLegacyMigrationRecords();

                return $this->executeMigrations();
            }

            $this->ensureCoreMigrations();
            $this->acquireLock();
            $this->log("Starting migration run for package: {$this->package}");

            if ($this->options['create_backup']) {
                $this->createBackup();
            }

            // Load migrations without resetting - we want to preserve existing data
            $this->toolkit->package($this->package)->action($this->action)->load();

            if (!$this->toolkit->isSuccess()) {
                throw new Exception($this->toolkit->getErrors());
            }

            // Execute migrations normally - don't drop existing tables
            $result = $this->executeMigrations();
            return $result;
            
        } catch (Exception $e) {
            $this->log('Migration run failed: ' . $e->getMessage(), 'error');
            if ($this->options['create_backup']) {
                $this->log('Consider restoring from backup if needed.', 'warning');
            }
            throw $e;
        } finally {
            if ($this->package !== 'platform') {
                $this->releaseLock();
            }
            $this->finalizeStatistics();
        }
    }

    private function ensureCoreMigrations(): void
    {
        (new self('platform', self::ACTION_RUN, $this->options))->run();
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
                'created_at' => $this->migrationCreatedAt($migration),
            ];
        }

        return $status;
    }

    private function migrationCreatedAt(array $migration): ?string
    {
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})_/', $migration['fileName'], $matches)) {
            return sprintf(
                '%s-%s-%s %s:%s:%s',
                $matches[1],
                $matches[2],
                $matches[3],
                $matches[4],
                $matches[5],
                $matches[6]
            );
        }

        $file = $migration['migrationFile'] ?? null;

        return is_string($file) && is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : null;
    }

    /**
     * Reset all migrations (rollback everything)
     */
    public function reset(): array
    {
        $this->log('Starting migration reset (rollback all)');

        try {
            // Load migrations first
            $this->toolkit->package($this->package)->action('status')->load();
            $migrations = array_reverse($this->toolkit->getMigrations());

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                // Get all tables in the database
                $tables = DB::select("SHOW TABLES LIKE '{$this->package}_%'");
                
                // Drop all matching tables
                foreach ($tables as $table) {
                    $tableName = reset($table);
                    $this->log("Dropping table: {$tableName}");
                    DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                }

                // Delete all migration records for this package
                HistoryModel::where('type', MigrationQuery::TYPE_MIGRATION)
                    ->where('app', $this->package)
                    ->delete();

                return ['Successfully reset all migrations for package: ' . $this->package];
            } finally {
                // Re-enable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } catch (Exception $e) {
            throw new Exception("Failed to reset migrations: " . $e->getMessage());
        }
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

        $executed = [];
        $skipped = [];
        
        try {
            // Disable foreign key checks for this session
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            foreach ($migrations as $migration) {
                $migrationName = $migration['fileName'];

                if ($this->shouldSkipMigrationExecution($migration)) {
                    $skipped[] = $migrationName;
                    $this->log("Skipping already executed migration: {$migrationName}");
                    continue;
                }

                try {
                    $this->log("Executing migration: {$migrationName}");
                    
                    // Execute the migration
                    MigrationBase::usePackage($this->package);
                    $migrationInstance = require $migration['migrationFile'];
                    MigrationBase::usePackage(null);
                    $migrationInstance->up();
                    
                    // Record that this migration has been run
                    $this->recordMigration($migrationName);
                    
                    $executed[] = $migrationName;
                    $this->log("Successfully executed migration: {$migrationName}");
                    
                } catch (Exception $e) {
                    MigrationBase::usePackage(null);

                    if ($e instanceof QueryException && $this->isTableAlreadyExistsError($e)) {
                        $this->log("Table already exists, adopting migration: {$migrationName}", 'warning');
                        $this->adoptExistingMigration($migrationName);
                        $skipped[] = $migrationName;

                        continue;
                    }

                    $this->log("Failed to execute migration {$migrationName}: " . $e->getMessage(), 'error');
                    throw new Exception("Migration {$migrationName} failed: " . $e->getMessage());
                }
            }
            
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        return [
            'executed' => $executed,
            'skipped' => $skipped,
            'total' => count($executed) + count($skipped)
        ];
    }
    
    /**
     * @param array<string, mixed> $migration
     */
    private function shouldSkipMigrationExecution(array $migration): bool
    {
        $migrationName = (string) ($migration['fileName'] ?? '');

        if ($migrationName === '') {
            return false;
        }

        $recorded = $this->hasBeenRun($migrationName) || !empty($migration['sync']);
        $tableExists = $this->targetTableExists($migration);

        if ($recorded && $tableExists) {
            return true;
        }

        if (!$recorded && $tableExists) {
            return $this->adoptExistingMigration($migrationName);
        }

        return false;
    }

    private function adoptExistingMigration(string $migrationName): bool
    {
        if (!$this->toolkit->isExistsMigrationTable()) {
            return false;
        }

        try {
            $this->recordMigration($migrationName);
            $this->log("Adopted existing schema for migration: {$migrationName}");
            $this->statistics['skipped_migrations']++;

            return true;
        } catch (Exception $e) {
            $this->log("Cannot adopt migration {$migrationName}: " . $e->getMessage(), 'warning');

            return false;
        }
    }

    private function hasBeenRun(string $migrationName): bool
    {
        try {
            return DB::table(DB::tableName(Table::HISTORY, 'platform'), null, 'platform')
                ->where('type', MigrationQuery::TYPE_MIGRATION)
                ->where('migration', $migrationName)
                ->where('app', $this->package)
                ->exists();
        } catch (Exception $e) {
            // If any error occurs (like table doesn't exist), assume migration hasn't been run
            $this->log("Error checking migration status for {$migrationName}: " . $e->getMessage(), 'warning');
            return false;
        }
    }

    /**
     * @param array<string, mixed> $migration
     */
    private function targetTableExists(array $migration): bool
    {
        $fileName = (string) ($migration['fileName'] ?? '');

        if (str_contains($fileName, 'create_access_tables')) {
            return $this->schemaHasTable(Table::ROLE, $this->package);
        }

        $table = $migration['tableName'] ?? null;

        if (!is_string($table) || $table === '') {
            return true;
        }

        return $this->schemaHasTable($table, $this->package);
    }

    private function schemaHasTable(string $table, ?string $package = null): bool
    {
        try {
            $package ??= $this->package;
            $connection = $package === 'platform'
                ? 'platform'
                : DB::connectionNameForPackage($package);
            $conn = DB::connection($connection);
            $physical = DB::physicalTableName($table, $package);
            $prefix = (string) $conn->getTablePrefix();
            $logical = $physical;

            if ($prefix !== '' && str_starts_with($physical, $prefix)) {
                $logical = substr($physical, strlen($prefix));
            }

            if (DB::schema($connection)->hasTable($logical)) {
                return true;
            }

            $database = (string) $conn->getDatabaseName();

            if ($database === '' || $physical === '') {
                return false;
            }

            $row = $conn->selectOne(
                'SELECT 1 AS found FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1',
                [$database, $physical],
            );

            return $row !== null;
        } catch (Exception $e) {
            $this->log('Error checking table ' . $table . ': ' . $e->getMessage(), 'warning');

            return false;
        }
    }
    
    private function recordMigration(string $migrationName): void
    {
        try {
            DB::table(DB::tableName(Table::HISTORY, 'platform'), null, 'platform')->insert([
                'type' => MigrationQuery::TYPE_MIGRATION,
                'migration' => $migrationName,
                'app' => $this->package,
                'batch' => $this->getBatchNumber(),
            ]);
            
            $this->log("Successfully recorded migration: {$migrationName}");
        } catch (Exception $e) {
            $this->log("Failed to record migration {$migrationName}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    private function getBatchNumber(): int
    {
        try {
            $lastBatch = DB::table(DB::tableName(Table::HISTORY, 'platform'), null, 'platform')
                ->where('type', MigrationQuery::TYPE_MIGRATION)
                ->where('app', $this->package)
                ->max('batch');
            return ($lastBatch ?? 0) + 1;
        } catch (Exception $e) {
            $this->log("Error getting batch number: " . $e->getMessage(), 'warning');
            return 1;
        }
    }
    
    private function extractMigrationName(string $filename): string
    {
        // Extract migration name from filename (remove .php extension)
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Execute a single migration
     */
    private function executeSingleMigration(array $migration, int $batch): void
    {
        $startTime = microtime(true);

        try {
            // Set execution timeout
            set_time_limit($this->timeout);

            // Include the migration file and get the class instance
            MigrationBase::usePackage($this->package);
            $migrationClass = require_once $migration['migrationFile'];
            MigrationBase::usePackage(null);

            if (!is_object($migrationClass) || !method_exists($migrationClass, 'up')) {
                throw new Exception("Migration {$migration['fileName']} does not have a valid 'up' method");
            }

            // Execute the migration
            $migrationClass->up();

            // Only record the migration if it was successful
            MigrationQuery::insert($migration['fileName'], $migration['packageName'], $batch);

            $executionTime = microtime(true) - $startTime;
            $this->log("Executed {$migration['fileName']} in " . round($executionTime, 2) . "s");

        } catch (Exception $e) {
            MigrationBase::usePackage(null);
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

            // Include the migration file and get the class instance
            MigrationBase::usePackage($this->package);
            $migrationClass = require_once $migrationFile;
            MigrationBase::usePackage(null);

            if (!is_object($migrationClass)) {
                throw new Exception("Migration {$migration['migration']} does not return a valid class instance");
            }

            if (method_exists($migrationClass, 'down')) {
                $migrationClass->down();
            } else {
                $this->log("Warning: Migration {$migration['migration']} does not have a 'down' method", 'warning');
            }

            MigrationQuery::delete($migration['migration'], $this->package);

            if ($this->useTransactions) {
                DB::commit();
            }

        } catch (Exception $e) {
            MigrationBase::usePackage(null);
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
        return $this->migrationRecordExists($migration['fileName'], $migration['packageName']);
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
            if (time() - $lockTime > 60) { // 1 minute timeout
                unlink($this->lockFile);
                $this->log('Removed stale migration lock', 'warning');
            } else {
                throw new Exception('Another migration is currently running for this package');
            }
        }

        file_put_contents($this->lockFile, time());
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

        try {
            Logger::channel('migration')->log($level, $message);
        } catch (\Throwable) {
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

    /**
     * Check if a migration is for creating the migration table
     */
    private function isMigrationTableCreation(array $migration): bool
    {
        return strpos($migration['fileName'], 'create_history_table') !== false
            || strpos($migration['fileName'], 'create_migration_table') !== false;
    }
}

