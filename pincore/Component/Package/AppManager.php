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


namespace Pinoox\Component\Package;


use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Component\Translator\Translator;

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

    public function router(string $path = ''): Router
    {
        return $this->appEngine->router($this->packageName, $path);
    }

    public function getAllRouters(): array
    {
        return $this->appEngine->getAllRouters($this->packageName);
    }

    public function config(): ConfigInterface
    {
        return $this->appEngine->config($this->packageName);
    }

    public function lang(): Translator
    {
        return $this->appEngine->lang($this->packageName);
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