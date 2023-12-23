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

namespace pinoox\component\kernel;

use pinoox\component\Helpers\Str;
use pinoox\component\package\AppManager;
use pinoox\portal\app\AppEngine;
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Terminal
{
    private Application $application;

    private array $commands = [];

    public function __construct()
    {
        $this->application = new Application();

    }

    public function run(): void
    {
        $this->finds();
        $this->bindCommands();

        try {
            $this->application->run();
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function addCommand()
    {

    }

    private function finds(): void
    {
        $this->loadTerminals(PINOOX_CORE_PATH);

        $packages = AppEngine::all();
        /**
         * @var AppManager $app
         */
        foreach ($packages as $app) {
            $this->loadTerminals($app->path(), $app->package());
        }
    }

    private function loadTerminals(string $path, ?string $package = null)
    {
        $path = Str::ds($path);
        if (!Str::lastHas($path, '/'))
            $path .= '/';
        if (!is_dir($path . 'terminal'))
            return;
        $finder = new Finder();
        $finder->in($path . 'terminal')
            ->files()
            ->filter(static function (SplFileInfo $file) {
                return $file->isDir() || \preg_match('/\Command.(php)$/', $file->getPathname());
            });

        /**
         * @var SplFileInfo $f
         */
        foreach ($finder as $f) {
            $loc = $f->getPath();
            $namespace = !empty($package) ? "pinoox" . '\\' . 'app' . '\\' . $package . '\\' : "pinoox" . '\\';
            $namespace = $namespace . str_replace($path, '', $loc) . '\\';
            $namespace = str_replace('/', '\\', $namespace);
            $this->commands[] = [
                'path' => $path,
                'fileName' => $f->getFilename(),
                'className' => $f->getBasename('.php'),
                'namespace' => $namespace,
            ];
        }
    }

    private function bindCommands(): void
    {
        if (empty($this->commands)) exit("there isn't any commands");

        //register commands
        foreach ($this->commands as $c) {
            $command = $c['namespace'] . $c['className'];
            $this->application->add(new $command());
        }

    }

}