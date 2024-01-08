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

namespace Pinoox\Component\Manager;

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\AppBuilder;
use Pinoox\Portal\Config;
use Symfony\Component\Finder\Finder;

class AppManager
{
    /**
     * base path of apps
     *
     */
    private string $basePath;

    /**
     *  apps list
     *
     */
    private array $apps;

    /**
     * app
     *
     */
    private array $app;

    private string $defaultMigrationPath = 'database/migrations';

    public function __construct()
    {
        $this->basePath = Loader::getBasePath() . '/apps/';
    }

    public function getApps(): array
    {
        $finder = new Finder();
        $finder->depth(0)->directories()->in($this->basePath);
        foreach ($finder as $folder) {
            $path = $folder->getRealPath();
            if (!file_exists($path . '/app.php')) continue;

            $this->apps[] = [
                'path' => $path,
                'package' => $folder->getFilename(),
                'size' => $folder->getSize(),
            ];
        }

        return $this->apps;
    }

    /**
     * @throws \Exception
     */
    public function getApp($packageName): array|null
    {
        if ($packageName === 'pincore') {
            $this->findPincore();
        } else {
            $this->findApp($packageName);
        }
        return $this->app;
    }

    /**
     * @throws \Exception
     */
    private function findApp($packageName): void
    {
        if (!is_dir($this->basePath . $packageName)) {
            throw new \Exception("The \"$packageName\" package could not found ");
        }

        $finder = new Finder();
        $finder->depth(0)->directories()->in($this->basePath . $packageName);
        $iterator = $finder->getIterator();
        $iterator->rewind();
        $folder = $iterator->current();
        $appPath = $folder->getPath() . '/app.php';
        if (!file_exists($appPath)) {
            throw new \Exception('The "app.php" file could not found in "' . $packageName . '"');
        };

        $app = AppBuilder::init($packageName)->get();

        $this->injectBasicParams(array: $app,
            package: $packageName,
            path: $folder->getPath() . '/',
            migration: $folder->getPath() . '/' . $this->getMigrationPath($app),
            namespace: $this->getNamespace($app)
        );

        $this->app = $app;
    }

    private function findPincore(): void
    {
        $pincore = Config::name('~pinoox')->get();
        $this->injectBasicParams(array: $pincore,
            package: 'pincore',
            path: PINOOX_CORE_PATH,
            migration: PINOOX_CORE_PATH . $this->defaultMigrationPath,
            namespace: 'pinoox');

        $this->app = $pincore;
    }

    private function getMigrationPath($app): string
    {
        if (empty($app)) return false;
        return str_replace( '\\', '/', $app['migration']['path'] ?? $this->defaultMigrationPath);
    }

    private function getNamespace($app): string
    {
        return 'App\\' . $app['package'];
    }

    private function injectBasicParams(array &$array = null, string $package = null, string $path = null, string $migration = null, string $namespace = null): void
    {
        if (isset($package)) $array['package'] = $package;
        if (isset($path)) $array['path'] = $path;
        if (isset($namespace)) $array['namespace'] = $namespace;
        if (isset($migration)) $array['migration'] = $migration;
        $array['migration_relative_path'] = $this->defaultMigrationPath;

    }

}

