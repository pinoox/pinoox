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

namespace Pinoox\Portal\Kernel;

use Pinoox\Component\Kernel\Listener\ActionRoutesManageListener;
use Pinoox\Component\Kernel\Listener\ExceptionListener;
use Pinoox\Component\Kernel\Listener\RequestListener;
use Pinoox\Component\Kernel\Listener\RouteEmptyListener;
use Pinoox\Component\Kernel\Listener\TransactionalListener;
use Pinoox\Component\Kernel\Listener\ViewListener;
use Pinoox\Component\Source\Portal;
use Pinoox\Controller\ErrorController;
use Pinoox\Portal\App\App;
use Pinoox\Portal\DB;
use Pinoox\Portal\Validation;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Pinoox\Component\Kernel\Listener\RouterListener;

class Listener extends Portal
{
    public static function __register(): void
    {
        self::__bind(RouterListener::class, 'router')
            ->setArgument('matcher', App::__ref())
            ->setArgument('requestStack', HttpKernel::__ref('request_stack'))
            ->setArgument('context', App::__ref('context'))
            ->setArgument('logger', null)
            ->setArgument('projectDir', null)
            ->setArgument('debug', false);

        self::__bind(RouteEmptyListener::class, 'routeEmpty');
        self::__bind(ViewListener::class, 'view');
        self::__bind(RequestListener::class, 'request')->setArguments([
            Validation::__ref(),
        ]);

        self::__bind(ActionRoutesManageListener::class, 'controller');

        self::__bind(ResponseListener::class, 'response')
            ->setArguments(['%charset%']);

        self::__bind(ExceptionListener::class, 'exception');

        self::__bind(ErrorListener::class, 'core_exception')
            ->setArguments([[ErrorController::class, 'exception']]);

        self::__bind(TransactionalListener::class, 'transactional')
            ->setArguments([DB::__ref()]);
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'kernel.listener';
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }
}
