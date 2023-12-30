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
use Pinoox\Component\Kernel\Listener\RouteListener;
use Pinoox\Component\Kernel\Listener\ViewListener;
use Pinoox\Component\Source\Portal;
use Pinoox\Controller\ErrorController;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;

class Listener extends Portal
{
    public static function __register(): void
    {
        self::__bind(RouterListener::class, 'router')
            ->setArgument('matcher', HttpKernel::__ref('matcher'))
            ->setArgument('requestStack', HttpKernel::__ref('request_stack'))
            ->setArgument('context', null)
            ->setArgument('logger', null)
            ->setArgument('projectDir', null)
            ->setArgument('debug', false);

        self::__bind(RouteListener::class, 'route');
        self::__bind(ViewListener::class, 'view');

        self::__bind(ActionRoutesManageListener::class, 'controller');

        self::__bind(ResponseListener::class, 'response')
            ->setArguments(['%charset%']);

        self::__bind(ErrorListener::class, 'exception')
            ->setArguments([[ErrorController::class, 'exception']]);
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
