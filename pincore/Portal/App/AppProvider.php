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
use Pinoox\Component\Package\App as ObjectPortal1;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Dumper;
use Pinoox\Portal\Env;
use Pinoox\Portal\Event;
use Pinoox\Portal\Kernel\HttpKernel;
use Pinoox\Portal\Kernel\Terminal;
use Pinoox\Portal\Session;
use Symfony\Component\ErrorHandler\Debug;

/**
 * @method static AppProvider prerequisite()
 * @method static Request getRequest()
 * @method static ObjectPortal1 getApp()
 * @method static AppProvider run(string $package = '', string $path = '/')
 * @method static meetingHandle(string $package, string $path, ?\Pinoox\Component\Http\Request $request = NULL, array $attributes = [])
 * @method static ObjectPortal2 handle(?\Pinoox\Component\Http\Request $request = NULL, int $type = 1)
 * @method static handleByRoute(string $package, ?\Pinoox\Component\Http\Request $request = NULL)
 * @method static ObjectPortal3 getKernel()
 * @method static AppProvider terminate(\Pinoox\Component\Http\Request $request, \Pinoox\Component\Http\Response $response)
 * @method static AppProvider boot()
 * @method static \Pinoox\Component\Package\AppProvider ___()
 *
 * @see \Pinoox\Component\Package\AppProvider
 */
class AppProvider extends Portal
{
    public static function __register(): void
    {
        self::__bind(\Pinoox\Component\Package\AppProvider::class)->setArguments([
            App::__ref(),
            HttpKernel::__ref(),
            Terminal::__ref(),
            Session::__ref(),
            Event::__ref(),
        ]);

        self::require();
    }

    private static function require(): void
    {
        Dumper::register();
        Debug::enable();
        Env::register();
        DB::register();
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'app.provider';
    }
}
