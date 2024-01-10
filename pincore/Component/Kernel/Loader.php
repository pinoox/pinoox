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

use Composer\Autoload\ClassLoader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Config;
use Pinoox\Component\Service;
use Pinoox\Portal\DB;
use Pinoox\Portal\Env;
use Pinoox\Portal\Dumper;
use Symfony\Component\ErrorHandler\Debug;

class Loader
{
    private static ClassLoader $classLoader;
    private static string $basePath;

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = str_replace('\\', '/', $basePath);
    }

    public static function getBasePath(): string
    {
        return self::$basePath;
    }

    public static function setClassLoader(ClassLoader $classLoader): void
    {
        self::$classLoader = $classLoader;
    }

    public static function getClassLoader(): ClassLoader
    {
        return self::$classLoader;
    }
}