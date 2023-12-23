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


namespace pinoox\component\package;


use pinoox\component\package\engine\EngineInterface;
use pinoox\component\Path\reference\ReferenceInterface;
use pinoox\component\router\Router;
use pinoox\component\store\config\ConfigInterface;

class AppManager
{
    public function __construct(
        private EngineInterface           $appEngine,
        private ReferenceInterface|string $packageName
    )
    {
    }

    public function stable(): bool
    {
        return $this->appEngine->stable($this->packageName);
    }

    public function routes(string $path = ''): Router
    {
        return $this->appEngine->routes($this->packageName, $path);
    }

    public function config(): ConfigInterface
    {
        return $this->appEngine->config($this->packageName);
    }

    public function exists(): bool
    {
        return $this->appEngine->exists($this->packageName);
    }

    public function package(): string
    {
        return $this->packageName;
    }

    public function path(string $path = ''): string
    {
        return $this->appEngine->path($this->packageName, $path);
    }

    public function supports(): bool
    {
        return $this->appEngine->supports($this->packageName);
    }
}