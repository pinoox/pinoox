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

namespace pinoox\portal;

use pinoox\component\package\reference\PathReference;
use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\source\Portal;
use pinoox\component\store\baker\Pinker as ObjectPortal1;

/**
 * @method static ObjectPortal1 create(?string $mainFile = NULL, ?string $bakedFile = NULL)
 * @method static Pinker ___()
 *
 * @see pinoox\component\store\baker\Pinker
 */
class Pinker extends Portal
{
    const folder = 'pinker';

    public static function __register(): void
    {
        self::__bind(ObjectPortal1::class);
    }

    public static function folder(string $path, string $file): ObjectPortal1
    {
        $mainFile = $path . DIRECTORY_SEPARATOR . $file;
        $mainFile = is_file($mainFile) ? $mainFile : '';

        $bakedFile = $path . DIRECTORY_SEPARATOR . self::folder . DIRECTORY_SEPARATOR . $file;

        return self::create($mainFile, $bakedFile);
    }

    /**
     * get pinker by file
     *
     * @param string|ReferenceInterface $fileName
     * @return ObjectPortal1
     */
    public static function file(string|ReferenceInterface $fileName): ObjectPortal1
    {
        $reference = Path::reference($fileName);
        $pathMain = $reference->getPackageName() === '~' ? 'pincore/' . $reference->getPath() : $reference->getPath();
        $pathBaked = $reference->getPackageName() === '~' ? 'pincore/' . self::folder . '/' . $reference->getPath() : $reference->getPath();

        $reference = PathReference::create(
            $reference->getPackageName(),
            $pathMain,
        );

        $mainFile = Path::get($reference);
        $mainFile = is_file($mainFile) ? $mainFile : '';

        $reference = PathReference::create(
            $reference->getPackageName(),
            $pathBaked,
        );

        $bakedFile = Path::get($reference);

        return self::create($mainFile, $bakedFile);
    }

    /**
     * get pinker by path
     *
     * @param string $file
     * @param string|null $basePath
     * @return ObjectPortal1
     */
    public static function path(string $file, ?string $basePath = null): ObjectPortal1
    {
        $basePath = !empty($basePath) ? $basePath . '/' : '';
        return self::create(
            self::ds($basePath . $file),
            self::ds($basePath . Pinker::folder . '/' . $file),
        );
    }

    public static function ds(string $path): string
    {
        return str_replace(['/', '\\', '>'], DIRECTORY_SEPARATOR, $path);
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'pinker';
    }


    /**
     * Get include method names .
     * @return string[]
     */
    public static function __include(): array
    {
        return ['file', 'create'];
    }

}
