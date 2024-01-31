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

use Illuminate\Database\Capsule\Manager;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\DB;
use Symfony\Component\Finder\Finder;

class MigrationToolkit
{

    private $schema = null;
    /**
     * @var Manager;
     */

    /**
     * package name
     * @var string
     */
    private string $package;
    private string $tableName;

    /**
     * path of migration files
     * @var string
     */
    private string $migrationPath;

    private string $migrationName;

    private string $migrationFolder = 'migrations';

    /**
     * actions: rollback,run,init,status
     * @var string
     */
    private string $action = 'run';

    /**
     * errors
     * @var array | string
     */
    private string|array $errors = [];

    /**
     * migration files list
     * @var array
     */
    private array $migrations = [];

    public function __construct()
    {
        $this->schema = DB::schema();
    }

    public function package($val): self
    {
        $this->package = $val;
        return $this;
    }

    /**
     * actions: init, run, rollback, create
     * @return $this
     */
    public function action($action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
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

                if ($this->action === 'rollback' && empty($m['sync'])) continue;
                if ($this->action === 'run' && !empty($m['sync'])) continue;

                try {
                    $this->migrations[] = [
                        'sync' => $m['sync'],
                        'packageName' => $this->package,
                        'migrationFile' => $migrationFile,
                        'fileName' => $fileName,
                    ];
                } catch (Exception $e) {
                    $this->setError($e);
                }
            }

        }

        return $this;
    }

    private function getFromDB(): ?array
    {
        $batch = $this->action == 'rollback' ?
            MigrationQuery::fetchLatestBatch($this->package) : null;

        return MigrationQuery::fetchAllByBatch($batch, $this->package);
    }

    /**
     * @throws \Exception
     */
    public function isExistsMigrationTable(): bool
    {
        return $this->schema->hasTable('pincore_migration');
    }

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
        $string = str_replace([' ', '_'], '-', $string);

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

        //find migrations in database
        return array_map(function ($m) use ($records) {
            $index = array_search($m['migration'], array_column($records, 'migration'));

            if ($index !== false) {
                $m['sync'] = $records[$index] ?? null;
            }
            return $m;
        }, $migrations);
    }

}