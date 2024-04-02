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

namespace Pinoox\Portal\Wizard;

use Pinoox\Component\Source\Portal;
use Pinoox\Component\Wizard\Wizard as ObjectPortal1;
use Pinoox\Portal\App\AppEngine;

/**
 * @method static array|bool install()
 * @method static \Pinoox\Component\Wizard\AppWizard open(string $path)
 * @method static bool isUpdateAvailable()
 * @method static bool isInstalled()
 * @method static \Pinoox\Component\Wizard\AppWizard migration($val = true)
 * @method static array|null getInfo()
 * @method static mixed getErrors(bool $last = false)
 * @method static array getMeta()
 * @method static AppWizard type($type)
 * @method static ObjectPortal1 force(bool $val = true)
 * @method static \Pinoox\Component\Wizard\AppWizard ___()
 *
 * @see \Pinoox\Component\Wizard\AppWizard
 */
class AppWizard extends Portal
{
     private const tmpPathRoot = 'wizard_tmp';

    public static function __register(): void
    {
        $tmpPath = path('pinker/' . self::tmpPathRoot);
        self::__bind(\Pinoox\Component\Wizard\AppWizard::class)->setArguments([
            $tmpPath,
            AppEngine::__ref()
        ]);
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'app.wizard';
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
        return [
            'type'
        ];
    }
}
