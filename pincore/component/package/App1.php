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

use Closure;
use Exception;
use pinoox\component\Dir;
use pinoox\component\kernel\Boot;

class App1
{
    /**
     * App structure
     *
     * @var AppLayer|null
     */
    private static ?AppLayer $app = null;

    /**
     * @param string $packageName
     * @param Closure $closure
     * @return mixed
     * @throws Exception
     */
    public static function meeting(string $packageName, Closure $closure): mixed
    {
        if (!self::exists($packageName))
            throw new Exception('package `' . $packageName . '` not found!');

        $app = self::$app;
        $path = Boot::$request?->getRequestUri() ?? '';
        self::$app = new AppLayer($path, $packageName);
        if (!is_callable($closure))
            throw new Exception('the value must be of function type');

        $result = $closure();

        self::$app = $app;

        return $result;
    }

    /**
     * Get the package name of the current application
     *
     * @return string|null
     */
    public static function package(): ?string
    {
        return self::$app?->getPackageName();
    }

    /**
     * Get App stake
     *
     * @return AppLayer
     */
    public static function current(): AppLayer
    {
        return self::$app;
    }

    /**
     * Get the URL of the current application
     *
     * @return string
     */
    public static function path(): string
    {
        return self::$app?->getPath();
    }

    /**
     * Set App stake
     *
     * @param AppLayer $app
     */
    public static function setLayer(AppLayer $app)
    {
        self::$app = $app;
    }

    /**
     * Set the package name of the current application
     *
     * @param string $packageAge
     * @throws Exception
     */
    public static function setPackageName(string $packageAge)
    {
        if (!self::exists($packageAge))
            throw new Exception('package `' . $packageAge . '` not found!');

        self::$app?->setPackageName($packageAge);
    }

    /**
     * Set the URL of the current application
     *
     * @param string $path
     */
    public static function setPath(string $path = '')
    {
        self::$app?->setPath($path);
    }


    /**
     * App exists
     * @param string $packageName
     * @return bool
     */
    public static function exists(string $packageName): bool
    {
        $file = Dir::path('~apps/' . $packageName . '/app.php');
        return is_file($file);
    }

    /**
     * Check App for use has stable
     *
     * @param string $packageName
     * @return bool
     */
    public static function stable(string $packageName): bool
    {
        $enable = false;

        if (App::exists($packageName)) {
            try {
                $enable = (bool)AppBuilder::init($packageName)->get('enable');
            } catch (Exception $e) {
            }
        }

        return $enable === true;
    }

    /**
     * Get data from config current app
     *
     * @param string|null $value
     * @return mixed
     */
    public static function get(?string $value = null): mixed
    {
        $packageName = self::$app?->getPackageName();

        if (empty($packageName))
            return null;

        try {
            return AppBuilder::init($packageName)->get($value);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Set data in config current app
     *
     * @param string $key
     * @param mixed $value
     * @return AppBuilder|null
     */
    public static function set(string $key, mixed $value): ?AppBuilder
    {
        return AppBuilder::init(self::$app?->getPackageName())->set($key, $value);
    }

    /**
     * Set data in config current app
     *
     * @param string $key
     * @param mixed $value
     * @return AppBuilder|null
     */
    public static function add(string $key, mixed $value): ?AppBuilder
    {
        return AppBuilder::init(self::$app?->getPackageName())->add($key, $value);
    }

    /**
     * Set data in config current app
     *
     * @return AppBuilder|null
     */
    public static function save(): ?AppBuilder
    {
        return AppBuilder::init(self::$app?->getPackageName())->save();
    }

    /**
     * Run App
     */
    public static function run(): void
    {
        Boot::handle();
    }
}

