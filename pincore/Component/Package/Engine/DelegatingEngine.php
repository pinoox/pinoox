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


namespace Pinoox\Component\Package\Engine;

use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Store\Config\Config;
use Pinoox\Component\Translator\Translator;

class DelegatingEngine implements EngineInterface
{
    /**
     * @var EngineInterface[]
     */
    protected array $engines = [];

    /**
     * @param EngineInterface[] $engines An array of EngineInterface instances to add
     */
    public function __construct(array $engines = [])
    {
        foreach ($engines as $engine) {
            $this->addEngine($engine);
        }
    }

    public function exists(ReferenceInterface|string $packageName): bool
    {
        return $this->getEngine($packageName)->exists($packageName);
    }

    public function config(ReferenceInterface|string $packageName): Config
    {
        return $this->getEngine($packageName)->config($packageName);
    }

    public function supports(ReferenceInterface|string $packageName): bool
    {
        try {
            $this->getEngine($packageName);
        } catch (\RuntimeException) {
            return false;
        }

        return true;
    }

    /**
     * Get an engine able to render the given template.
     *
     * @param string|ReferenceInterface $packageName
     * @return EngineInterface
     */
    public function getEngine(string|ReferenceInterface $packageName): EngineInterface
    {
        foreach ($this->engines as $engine) {
            if ($engine->supports($packageName)) {
                return $engine;
            }
        }

        throw new \RuntimeException(sprintf('No engine is able to work with the package name "%s".', $packageName));
    }

    public function addEngine(EngineInterface $engine): void
    {
        $this->engines[] = $engine;
    }

    public function path(ReferenceInterface|string $packageName, string $path = ''): string
    {
        $this->getEngine($packageName)->path($packageName);
    }

    public function lang(ReferenceInterface|string $packageName): Translator
    {
        $this->getEngine($packageName)->lang($packageName);
    }

    public function router(ReferenceInterface|string $packageName, string $path = ''): Router
    {
        $this->getEngine($packageName)->router($packageName);
    }

    public function stable(ReferenceInterface|string $packageName): bool
    {
        $this->getEngine($packageName)->stable($packageName);
    }
}