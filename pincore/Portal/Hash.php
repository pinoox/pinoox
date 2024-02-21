<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Illuminate\Contracts\Container\Container as ObjectPortal5;
use Illuminate\Hashing\Argon2IdHasher as ObjectPortal3;
use Illuminate\Hashing\ArgonHasher as ObjectPortal2;
use Illuminate\Hashing\BcryptHasher as ObjectPortal1;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Manager as ObjectPortal4;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Source\Portal;

/**
 * @method static ObjectPortal1 createBcryptDriver()
 * @method static ObjectPortal2 createArgonDriver()
 * @method static ObjectPortal3 createArgon2idDriver()
 * @method static array info($hashedValue)
 * @method static string make($value, array $options = [])
 * @method static bool check($value, $hashedValue, array $options = [])
 * @method static bool needsRehash($hashedValue, array $options = [])
 * @method static bool isHashed($value)
 * @method static string getDefaultDriver()
 * @method static mixed driver($driver = NULL)
 * @method static ObjectPortal4 extend($driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static ObjectPortal5 getContainer()
 * @method static ObjectPortal4 setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static ObjectPortal4 forgetDrivers()
 * @method static \Illuminate\Hashing\HashManager ___()
 *
 * @see \Illuminate\Hashing\HashManager
 */
class Hash extends Portal
{
    public static function __register(): void
    {
        self::__bind(HashManager::class)->setArguments([
            Container::Illuminate()
        ]);
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'hash';
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }
}
