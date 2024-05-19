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


namespace Pinoox\Component\Kernel\Listener;


use Pinoox\Component\Http\Response;
use Pinoox\Component\Http\ResponseException;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Portal\Kernel\Dispatcher;
use Pinoox\Portal\Kernel\Listener;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public function onException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof ResponseException) {
            $event->setResponse(
                $exception->getResponse()
            );
        } else if ($event->getRequest()->attributes->has('_controller')) {
            $controller = $event->getRequest()->attributes->get('_controller');
            if (is_array($controller) && isset($controller[0]) && class_exists($controller[0]) && method_exists($controller[0], '_exception')) {
                $result = call_user_func_array(array($controller[0], '_exception'), [
                    $event,
                ]);
                $event->setResponse($result);
                $event->stopPropagation();
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', -1],
        ];
    }
}