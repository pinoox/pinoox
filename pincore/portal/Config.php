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

use pinoox\component\File;
use pinoox\component\Path\reference\ReferenceInterface;
use pinoox\component\source\Portal;
use pinoox\component\store\config\Config as ObjectPortal1;
use pinoox\component\store\config\data\DataManager;
use pinoox\component\store\config\strategy\FileConfigStrategy;

/**
 * @method static \pinoox\component\store\config\Config create(\pinoox\component\store\config\strategy\ConfigStrategyInterface $strategy)
 * @method static \pinoox\component\store\config\strategy\FileConfigStrategy ___strategy()
 * @method static \pinoox\component\store\config\Config ___()
 *
 * @see \pinoox\component\store\config\Config
 */
class Config extends Portal
{
    const folder = 'config';
    const ext = 'config.php';


    public static function __register(): void
    {
        self::__bind(FileConfigStrategy::class, 'strategy')->setArguments([
            Pinker::__ref(),
        ]);

        self::__bind(ObjectPortal1::class)->setArguments([
            self::__ref('strategy')
        ]);
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

    public static function file(string $file): ObjectPortal1
    {
        $pinker = Pinker::create($file, $file);
        return self::create(new FileConfigStrategy($pinker));
    }

    private static function initFileConfig(string $fileName): ObjectPortal1
    {
        $fileName = $fileName . '.' . self::ext;
        $ref = Path::prefixReference($fileName, self::folder);
        $pinker = Pinker::file($ref);
        return self::create(new FileConfigStrategy($pinker));
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
     * Get include method names .
     * @return string[]
     */
    public static function __include(): array
    {
        return ['name', 'create'];
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
