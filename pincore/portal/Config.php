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

use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\source\Portal;
use pinoox\component\store\config\Config as ObjectPortal1;
use pinoox\component\store\config\data\DataArray;
use pinoox\component\store\config\strategy\FileConfigStrategy;

/**
 * @method static Config save()
 * @method static mixed get(?string $key = NULL)
 * @method static ObjectPortal1 add(string $key, mixed $value)
 * @method static ObjectPortal1 set(string $key, mixed $value)
 * @method static ObjectPortal1 remove(string $key)
 * @method static ObjectPortal1 merge(array $array)
 * @method static ObjectPortal1 reset()
 * @method static ObjectPortal1 restore()
 * @method static setLinear(string $key, string $target, mixed $value)
 * @method static getLinear(string $key, string $target)
 * @method static \pinoox\component\store\config\Config  object()
 *
 * @see \pinoox\component\store\config\Config
 */
class Config extends Portal
{
    const folder = 'config';

    public static function __register(): void
    {
        self::__bind(ObjectPortal1::class)->setArguments([]);
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'config';
    }

    /**
     * Set file for pinoox baker
     *
     * @param string|ReferenceInterface $fileName
     * @return ObjectPortal1
     */
    public static function name(string|ReferenceInterface $fileName): ObjectPortal1
    {
        return self::initFileConfig($fileName);
    }

    private static function initFileConfig(string $fileName): ObjectPortal1
    {
        $fileName = $fileName . '.config.php';
        $ref = Path::prefixReference($fileName, self::folder);
        $pinker = Pinker::file($ref);
        $array = new DataArray($pinker->pickup());
        return new ObjectPortal1(new FileConfigStrategy($pinker, $array));
    }

    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [
            'save'
        ];
    }
}
