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


namespace Pinoox\Component\Package\Loader;


final class ChainLoader implements LoaderInterface
{
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
        foreach ($this->loaders as $loader) {
            if ($loader->exists($packageName)) {
                return true;
            }
        }

        return false;
    }
}
