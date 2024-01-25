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


namespace Pinoox\Component\Kernel\listener;

use Pinoox\Component\Kernel\Kernel;
use Symfony\Component\HttpKernel\EventListener\RouterListener as RouterListenerSymfony;
use Symfony\Component\HttpKernel\KernelEvents;

class RouterListener extends RouterListenerSymfony
{
    public static function getSubscribedEvents(): array
    {
        return [
            Kernel::HANDLE_BEFORE => [['onKernelRequest', 32]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
            KernelEvents::EXCEPTION => ['onKernelException', -64],
        ];
    }
}