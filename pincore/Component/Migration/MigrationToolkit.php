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

use Illuminate\Database\Schema\Builder;
use Pinoox\Model\Table;
use Pinoox\Model\MigrationModel;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Database\DB;
use Symfony\Component\Finder\Finder;

class MigrationToolkit
{
    // Constants for actions
    private const ACTION_RUN = 'run';
    private const ACTION_ROLLBACK = 'rollback';
    private const ACTION_INIT = 'init';
    private const ACTION_CREATE = 'create';
    private const ACTION_STATUS = 'status';

    // Constants for patterns
    private const TIMESTAMP_PATTERN = '/^\d{4}_\d{2}_\d{2}_\d{6}_/';
    private const MIGRATION_FILENAME_PATTERN = '/(\d{4}_\d{2}_\d{2}_\d{6})/';

    // Table name extraction patterns (order matters - more specific patterns first)
    private const TABLE_NAME_PATTERNS = [
        'create_table' => '/^create_(.+)_table$/',
        'drop_table' => '/^drop_(.+)_table$/',
        'alter_table' => '/^alter_(.+)_table$/',
        'add_to' => '/^add_.+_to_(.+)$/',
        'drop_from' => '/^drop_.+_from_(.+)$/',
        'remove_from' => '/^remove_.+_from_(.+)$/',
        'modify_in' => '/^modify_.+_in_(.+)$/',
        'update_in' => '/^update_.+_in_(.+)$/',
        'rename_in' => '/^rename_.+_in_(.+)$/',
    ];

    private Builder $schema;
    private string $package = '';
    private string $migrationPath = '';
    private string $migrationName = '';
    private string $tableName = '';
    private string $migrationFolder = 'migrations';
    private string $action = self::ACTION_RUN;
    private array $errors = [];
    private array $migrations = [];

    public function __construct()
    {
        $this->schema = DB::schema();
    }

    public function package(string $package): self
    {
        $this->package = $package;
        return $this;
    }

    public function schema(): Builder
    {
        return $this->schema;
    }

    public function action(string $action): self
    {
        $validActions = [self::ACTION_RUN, self::ACTION_ROLLBACK, self::ACTION_INIT, self::ACTION_CREATE, self::ACTION_STATUS];

        if (!in_array($action, $validActions)) {
            throw new \InvalidArgumentException("Invalid action: {$action}. Valid actions are: " . implode(', ', $validActions));
        }

        $this->action = $action;
        return $this;
    }

    /**
     * Load migration files and prepare them for execution
     */
    public function load(): self
    {
        try {
            $this->initializeMigrationPath();
            $migrations = $this->loadMigrationFiles();

            if (empty($migrations)) {
                return $this;
            }

            // If migration table doesn't exist, create it first
            if (!$this->isExistsMigrationTable()) {
                $migrationTableMigration = null;
                foreach ($migrations as $migration) {
                    $migrationInfo = $this->extractMigrationInfo($migration);
                    if (strpos($migrationInfo['fileName'], 'create_migration_table') !== false) {
                        $migrationTableMigration = $migrationInfo;
                        break;
                    }
                }

                if ($migrationTableMigration !== null) {
                    $this->migrations = [$migrationTableMigration];
                    return $this;
                }
            }

            // For other cases, proceed normally
            if ($this->shouldSyncWithDatabase()) {
                $migrations = $this->syncWithDatabase($migrations);
            }

            $this->processMigrations($migrations);
        } catch (\Exception $e) {
            $this->addError($e);
        }

        return $this;
    }

    public function isExistsMigrationTable(): bool
    {
        try {
            return $this->schema->hasTable(Table::MIGRATION);
        } catch (\Exception $e) {
            $this->addError($e);
            return false;
        }
    }

    public function getMigrations(): array
    {
        return $this->migrations;
    }

    public function filePath(): string
    {
        return $this->migrationPath . '/' . $this->migrationName;
    }

    public function generateMigrationFileName(string $modelName): void
    {
        $timestamp = date('Y_m_d_His');
        $modelName = $this->toSnakeCase($modelName);
        $name = 'create_' . $modelName . '_table';

        $this->migrationName = $timestamp . '_' . $name;
        $this->tableName = $this->makeTableName($modelName);
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getMigrationPath(): string
    {
        return $this->migrationPath;
    }

    public function getMigrationName(): string
    {
        return $this->migrationName;
    }

    public function getErrors(bool $latest = true): array|string
    {
        if ($latest) {
            return end($this->errors) ?: '';
        }
        return $this->errors;
    }

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    /**
     * Initialize migration path based on package
     */
    private function initializeMigrationPath(): void
    {
        if ($this->package === 'pincore') {
            $this->migrationPath = path('~pincore') . '/Database/' . $this->migrationFolder;
        } else {
            $this->migrationPath = AppEngine::path($this->package) . '/' . $this->migrationFolder;
        }
    }

    /**
     * Load migration files from the migration directory
     */
    private function loadMigrationFiles(): array
    {
        $this->ensureMigrationDirectoryExists();

        $files = [];
        $finder = new Finder();

        if (!is_dir($this->migrationPath)) {
            return $files;
        }

        $finder->in($this->migrationPath)->files()->name('*.php');

        foreach ($finder as $file) {
            $filename = $file->getBasename('.php');

            if ($this->shouldSkipFile($filename)) {
                continue;
            }

            $timestamp = $this->extractTimestamp($filename);
            if (!$timestamp) {
                continue;
            }

            $files[] = [
                'file' => $filename,
                'path' => $file->getRealPath(),
                'sync' => false,
                'timestamp' => $timestamp,
            ];
        }

        return $this->sortFilesByTimestamp($files);
    }

    /**
     * Check if we should sync migrations with database
     */
    private function shouldSyncWithDatabase(): bool
    {
        return !in_array($this->action, [self::ACTION_CREATE, self::ACTION_INIT])
            && $this->isExistsMigrationTable();
    }

    /**
     * Process migration files and prepare them for execution
     */
    private function processMigrations(array $migrations): void
    {
        $this->migrations = [];
        $migrationTableMigration = null;

        foreach ($migrations as $migration) {
            if ($this->shouldSkipMigration($migration)) {
                continue;
            }

            $migrationInfo = $this->extractMigrationInfo($migration);

            // If this is the migration table creation migration, handle it separately
            if (strpos($migrationInfo['fileName'], 'create_migration_table') !== false) {
                $migrationTableMigration = $migrationInfo;
                continue;
            }

            $this->migrations[] = $migrationInfo;
        }

        // Sort all other migrations by timestamp
        if (!empty($this->migrations)) {
            $this->migrations = $this->sortFilesByTimestamp($this->migrations);
        }

        // If we found the migration table creation migration, put it first
        if ($migrationTableMigration !== null) {
            array_unshift($this->migrations, $migrationTableMigration);
        }
    }

    /**
     * Check if a migration file should be skipped based on action
     */
    private function shouldSkipFile(string $filename): bool
    {
        if ($this->action === self::ACTION_INIT && !str_contains($filename, 'migration')) {
            return true;
        }

        if ($this->action === self::ACTION_RUN && str_contains($filename, 'migration')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a migration should be skipped based on sync status
     */
    private function shouldSkipMigration(array $migration): bool
    {
        if ($this->action === self::ACTION_ROLLBACK && empty($migration['sync'])) {
            return true;
        }

        if ($this->action === self::ACTION_RUN && !empty($migration['sync'])) {
            return true;
        }

        return false;
    }

    /**
     * Extract timestamp from migration filename
     */
    private function extractTimestamp(string $filename): ?string
    {
        if (preg_match(self::MIGRATION_FILENAME_PATTERN, $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Sort files by timestamp
     */
    private function sortFilesByTimestamp(array $files): array
    {
        usort($files, function ($a, $b) {
            return strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? '');
        });

        return $files;
    }

    /**
     * Extract migration information from a migration file
     */
    private function extractMigrationInfo(array $migration): array
    {
        try {
            $fileName = $this->getFileName($migration);
            $migrationFile = $migration['path'];
            $tableName = $this->extractTableName($fileName);

            return [
                'sync' => $migration['sync'] ?? false,
                'packageName' => $this->package,
                'migrationFile' => $migrationFile,
                'fileName' => $fileName,
                'tableName' => $tableName,
            ];
        } catch (\Exception $e) {
            $this->addError($e);
            throw $e;
        }
    }

    /**
     * Extract the table name from migration filename using pattern matching
     */
    private function extractTableName(string $fileName): ?string
    {
        // Remove timestamp prefix
        $cleanFileName = preg_replace(self::TIMESTAMP_PATTERN, '', $fileName);

        // Try each pattern until we find a match
        foreach (self::TABLE_NAME_PATTERNS as $patternName => $pattern) {
            if (preg_match($pattern, $cleanFileName, $matches)) {
                return $matches[1];
            }
        }

        // Fallback: use the last meaningful part
        return $this->extractTableNameFallback($cleanFileName);
    }

    /**
     * Fallback method to extract table name when no pattern matches
     */
    private function extractTableNameFallback(string $fileName): ?string
    {
        $parts = explode('_', $fileName);

        if (count($parts) >= 2) {
            // Return the last part as potential table name
            return end($parts);
        }

        return null;
    }

    /**
     * Ensure migration directory exists
     */
    private function ensureMigrationDirectoryExists(): void
    {
        if (!file_exists($this->migrationPath)) {
            mkdir($this->migrationPath, 0755, true);
        }
    }

    /**
     * Convert string to snake_case
     */
    private function toSnakeCase(string $string): string
    {
        $string = str_replace([' ', '_'], '_', $string);
        return strtolower($string);
    }

    /**
     * Create table name with package prefix
     */
    private function makeTableName(string $modelName): string
    {
        $packageName = AppEngine::config($this->package)->get('package');
        return $packageName . '_' . $this->toSnakeCase($modelName);
    }

    /**
     * Get filename from migration item
     */
    private function getFileName(array|string $file): string
    {
        if (is_string($file)) {
            return $file;
        }
        return $file['file'];
    }

    /**
     * Add error to the errors array
     */
    private function addError(\Exception|\Throwable|string $error): void
    {
        if ($error instanceof \Exception || $error instanceof \Throwable) {
            $this->errors[] = $error->getMessage();
        } else {
            $this->errors[] = $error;
        }
    }

    /**
     * Sync migrations with database records
     */
    private function syncWithDatabase(array $migrations): array
    {
        $dbMigrations = $this->getFromDatabase();

        if (empty($dbMigrations)) {
            return $migrations;
        }

        foreach ($migrations as &$migration) {
            $fileName = $this->getFileName($migration);
            foreach ($dbMigrations as $dbMigration) {
                if ($fileName === $dbMigration['migration']) {
                    $migration['sync'] = true;
                    break;
                }
            }
        }

        return $migrations;
    }

    /**
     * Get migrations from database
     */
    private function getFromDatabase(): ?array
    {
        try {
            if (!$this->isExistsMigrationTable()) {
                return null;
            }

            return MigrationModel::where('app', $this->package)->get()->toArray();
        } catch (\Exception $e) {
            $this->addError($e);
            return null;
        }
    }
}