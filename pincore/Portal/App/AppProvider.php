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

namespace Pinoox\Portal\App;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response as ObjectPortal2;
use Pinoox\Component\Kernel\Kernel as ObjectPortal3;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Package\App as ObjectPortal1;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\Kernel\HttpKernel;

/**
 * @method static AppProvider prerequisite()
 * @method static Request getRequest()
 * @method static ObjectPortal1 getApp()
 * @method static AppProvider run()
 * @method static meetingHandle(string $package, string $path, ?\Pinoox\Component\Http\Request $request = NULL, array $attributes = [])
 * @method static ObjectPortal2 handle(?\Pinoox\Component\Http\Request $request = NULL, int $type = 1)
 * @method static handleByRoute(string $package, ?\Pinoox\Component\Http\Request $request = NULL)
 * @method static ObjectPortal3 getKernel()
 * @method static AppProvider terminate(\Pinoox\Component\Http\Request $request, \Pinoox\Component\Http\Response $response)
 * @method static \Pinoox\Component\Package\AppProvider ___()
 *
 * @see \Pinoox\Component\Package\AppProvider
 */
class AppProvider extends Portal
{

    /**
     * @var HttpKernel[]
     */
    private static array $httpKernels = [];

    public static function __register(): void
    {
        self::__bind(\Pinoox\Component\Package\AppProvider::class)->setArguments([
            App::__ref(),
            HttpKernel::__ref()
        ]);
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'app.provider';
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
            'run'
        ];
    }
}