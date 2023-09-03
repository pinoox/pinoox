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


namespace pinoox\component\package\loader;


final class ChainLoader implements LoaderInterface
{
    /**
     * @var bool[]
     */
    private array $hasPath = [];

    /**
     * @var LoaderInterface[]
     */
    private array $loaders = [];

    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    public function addLoader(LoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
    }

    /**
     * @return LoaderInterface[]
     */
    public function getLoaders(): array
    {
        return $this->loaders;
    }

    public function path(string $packageName): string
    {
        foreach ($this->loaders as $loader) {
            if (!$loader->exists($packageName)) {
                continue;
            }

            return $loader->path($packageName);
        }

        throw new \Exception(sprintf('Package "%s" is not defined.', $packageName));

    }

    public function exists(string $packageName): bool
    {
        if (isset($this->hasSourceCache[$packageName])) {
            return $this->hasPath[$packageName];
        }

        foreach ($this->loaders as $loader) {
            if ($loader->exists($packageName)) {
                return $this->hasPath[$packageName] = true;
            }
        }

        return $this->hasPath[$packageName] = false;
    }
}
