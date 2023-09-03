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
        $finder = new Finder();
        $finder->in(PINOOX_CORE_PATH . 'terminal')
            ->files()
            ->filter(static function (SplFileInfo $file) {
                return $file->isDir() || \preg_match('/\Command.(php)$/', $file->getPathname());
            });

        foreach ($finder as $f) {
            $path = $f->getPath();
            $namespace = "pinoox" . '\\' . str_replace(PINOOX_CORE_PATH, '', $path) . '\\';
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