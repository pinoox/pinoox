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


namespace Pinoox\Component\Kernel;

use Pinoox\Portal\App\App;
use Symfony\Component\DependencyInjection\Reference;
use Illuminate\Container\Container as ContainerIlluminate;

class Container
{
    const pincore = 'pincore';

    /** @var ContainerBuilder[] */
    private static array $containers;

    private static ContainerIlluminate $containerIlluminate;

    /**
     * Open a container
     *
     * @param string $name
     * @return ContainerBuilder
     */
    public static function open(string $name): ContainerBuilder
    {
        if (!isset(self::$containers[$name]))
            self::$containers[$name] = new ContainerBuilder();

        return self::$containers[$name];
    }

    /**
     * Destroy container
     *
     * @param string $name
     */
    public static function destroy(string $name): void
    {
        if (isset(self::$containers[$name]) && $name !== self::pincore)
            unset(self::$containers[$name]);
    }

    /**
     * Reference represents a service reference.
     *
     * @param string $id
     * @return Reference
     */
    public static function ref(string $id): Reference
    {
        return new Reference($id);
    }

    /**
     * Open default container
     *
     * @return ContainerBuilder
     */
    public static function pincore(): ContainerBuilder
    {
        return self::open(self::pincore);
    }

    public static function app(?string $packageName = null): ContainerBuilder
    {
        $packageName = empty($packageName) ? App::package() : $packageName;
        if ($packageName === '~')
            return self::pincore();
        else
            return self::open($packageName);
    }

    public static function Illuminate(): ContainerIlluminate
    {
        if (empty(self::$containerIlluminate))
            self::$containerIlluminate = new ContainerIlluminate();
        return self::$containerIlluminate;
    }
}