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
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Database\DB;
use Symfony\Component\Finder\Finder;

class MigrationToolkit
{
    /**
     * The schema manager instance.
     * @var Builder
     */
    private $schema;

    /**
     * Package name
     * @var string
     */
    private string $package;

    /**
     * Path of migration files
     * @var string
     */
    private string $migrationPath;

    private string $migrationName;

    private string $migrationFolder = 'migrations';

    /**
     * Actions: rollback, run, init, status
     * @var string
     */
    private string $action = 'run';

    /**
     * Errors
     * @var array|string
     */
    private string|array $errors = [];

    /**
     * Migration files list
     * @var array
     */
    private array $migrations = [];

    public function __construct()
    {
        // Use DB::schema() directly to get the schema manager
        $this->schema = DB::schema(); // Make sure DB::schema() returns a valid schema instance
    }

    public function package($val): self
    {
        $this->package = $val;
        return $this;
    }

    /**
     * Provide access to the schema manager.
     * @return Builder
     */
    public function schema(): Builder
    {
        return $this->schema;
    }

    /**
     * Actions: init, run, rollback, create
     * @return $this
     */
    public function action($action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Load migration files.
     * @throws \Exception
     */
    public function load(): self
    {
        $this->findMigrationPath();
        $migrations = $this->loadFiles();
 
        if (empty($migrations)) return $this;

        if ($this->action != 'create' && $this->action != 'init' && $this->isExistsMigrationTable()) {
            $migrations = $this->syncWithDB($migrations);
        }

        if (!empty($migrations)) {
            foreach ($migrations as $m) {
                list($fileName, $migrationFile) = $this->extract($m);
                $tableName = $this->extractTableName($fileName); // Extract table name

                if ($this->action === 'rollback' && empty($m['sync'])) continue;
                if ($this->action === 'run' && !empty($m['sync'])) continue;

                if ($this->tableExists($tableName) && $this->migrationRecordExists($fileName, $this->package)) {
                    $messages[] = '⚠️ [' . $fileName . '] skipped (table already exists and record present)';
                    continue;
                }

                try {
                    $this->migrations[] = [
                        'sync' => $m['sync'],
                        'packageName' => $this->package,
                        'migrationFile' => $migrationFile,
                        'fileName' => $fileName,
                        'tableName' => $tableName, // Add the table name to the migration array
                    ];
                } catch (\Exception $e) {
                    $this->setError($e);
                }
            }
        }

        return $this;
    }

    /**
     * Check if the migration table exists in the database.
     * @throws \Exception
     */
    public function isExistsMigrationTable(): bool
    {
        // Use the schema manager to check if the migration table exists
        return $this->schema->hasTable(Table::MIGRATION);
    }

    /**
     * Load migration files from the migration path.
     * @return array
     */
    private function loadFiles(): array
    {
        if (!file_exists($this->migrationPath)) {
            mkdir($this->migrationPath, 0755, true);
        }

        $files = [];
        $finder = new Finder();
        $finder->in($this->migrationPath)->files();

        foreach ($finder as $f) {
            $filename = $f->getBasename('.php');

            if ($this->action == 'init' && !str_contains($filename, 'migration')
                || $this->action == 'run' && str_contains($filename, 'migration')) {
                continue;
            }

            preg_match('/(\d{4}_\d{2}_\d{2}_\d{6})/', $filename, $matches);
            $timestamp = $matches[1] ?? null;
            if (!$timestamp) continue;

            $files[] = [
                'sync' => false,
                'path' => $f->getRealPath(),
                'migration' => $filename,
                'timestamp' => $timestamp,
            ];
        }

        // Sort files based on the timestamp in ascending order
        usort($files, function ($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });

        return $files;
    }

    private function extract($item): array
    {
        $fileName = $this->getFileName($item);
        $migrationFile = $this->migrationPath . '/' . $fileName . '.php';

        return [$fileName, $migrationFile];
    }

    public function getMigrations(): array
    {
        return $this->migrations;
    }

    public function filePath(): string
    {
        return $this->migrationPath . '/' . $this->migrationName;
    }

    public function generateMigrationFileName($modelName): void
    {
        // Get the current timestamp in the required format
        $timestamp = date('Y_m_d_His');
        $modelName = $this->snakeCase($modelName);

        // Convert the model name to snake_case and add "create_" prefix
        $name = 'create_' . $modelName . '_table';

        $this->migrationName = $timestamp . '_' . $name;
        $this->tableName = $this->makeTableName($modelName);
    }

    private function makeTableName($modelName): string
    {
        return AppEngine::config($this->package)->get('package') . '_' . $this->snakeCase($modelName);
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    private function snakeCase($string): string
    {
        // Replace spaces and underscores with dashes
        $string = str_replace([' ', '_'], '_', $string);

        // Convert the string to lowercase
        return strtolower($string);
    }

    private function getFileName($file): string
    {
        if (is_array($file)) {
            return $file['migration'];
        }
        return basename($file, '.php');
    }

    private function findMigrationPath(): void
    {
        if ($this->package == 'pincore')
            $this->migrationPath = path('~pincore') . '/Database/' . $this->migrationFolder;
        else
            $this->migrationPath = AppEngine::path($this->package) . '/' . $this->migrationFolder;
    }

    /**
     * Extract the table name from the migration file name.
     * Assumes file names follow the convention `timestamp_create_tableName_table`.
     * @param string $fileName
     * @return string|null
     */
    private function extractTableName(string $fileName): ?string
    {
        if (preg_match('/create_(.+)_table/', $fileName, $matches)) {
            $baseName = $matches[1];
            // Add prefix based on package
            if ($this->package === 'pincore') {
                return 'pincore_' . $baseName;
            } else {
                return $this->package . '_' . $baseName;
            }
        }
        return null;
    }

    public function getMigrationPath(): string
    {
        return $this->migrationPath;
    }

    public function getMigrationName(): string
    {
        return $this->migrationName;
    }

    public function getErrors($end = true)
    {
        if ($end) return end($this->errors);
        return $this->errors;
    }

    public function isSuccess(): bool
    {
        if (empty($this->getErrors()))
            return true;
        return false;
    }

    private function setError($err): void
    {
        $this->errors[] = $err;
    }

    private function syncWithDB($migrations): array
    {
        if (empty($migrations)) return [];

        $records = $this->getFromDB();

        // Find migrations in database
        return array_map(function ($m) use ($records) {
            $index = array_search($m['migration'], array_column($records, 'migration'));

            if ($index !== false) {
                $m['sync'] = $records[$index] ?? null;
            }
            return $m;
        }, $migrations);
    }

    private function getFromDB(): ?array
    {
        $batch = $this->action == 'rollback' ?
            MigrationQuery::fetchLatestBatch($this->package) : null;

        return MigrationQuery::fetchAllByBatch($batch, $this->package);
    }

    /**
     * Check if a table exists in the database.
     * @param string $tableName
     * @return bool
     */
    private function tableExists(string $tableName): bool
    {
        echo "Checking for table: $tableName\n";
        return $this->schema->hasTable($tableName);
    }

    private function migrationRecordExists(string $fileName, string $packageName): bool
    {
        echo "Checking migration record: $fileName, $packageName\n";
        return MigrationQuery::is_exists($fileName, $packageName);
    }
}