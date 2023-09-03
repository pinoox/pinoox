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


namespace pinoox\component\kernel\listener;


use pinoox\component\http\Response;
use pinoox\component\kernel\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    private mixed $controllerResult = null;

    public function onException(ExceptionEvent $event)
    {
        if (empty($this->controllerResult) && !is_bool($this->controllerResult)) {
            $event->setResponse(new Response(''));
        }

        $msg = null;
        if (!($this->controllerResult instanceof Response))
            $msg = sprintf('The controller must return a "pinoox\component\http\Response" object but it returned %s.', $this->varToString($this->controllerResult));
        else if (!$event->hasResponse())
            $msg = 'Did you forget to add a return statement somewhere in your controller?';

        if (null !== $msg) {
            $exception = new Exception($msg);
            $exception->setLine($event->getThrowable()->getLine());
            $exception->setCode($event->getThrowable()->getCode());
            $exception->setFile($event->getThrowable()->getFile());
            $event->setThrowable($exception);
        }

    }

    public function onView(ViewEvent $event)
    {
        $this->controllerResult = $event->getControllerResult();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onException', -1],
            KernelEvents::VIEW => ['onView', -21],
        ];
    }

    private function varToString(mixed $var): string
    {
        if (\is_object($var)) {
            return sprintf('an object of type %s', \get_class($var));
        }

        if (\is_array($var)) {
            $a = [];
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => ...', $k);
            }

            return sprintf('an array ([%s])', mb_substr(implode(', ', $a), 0, 255));
        }

        if (\is_resource($var)) {
            return sprintf('a resource (%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'a boolean value (false)';
        }

        if (true === $var) {
            return 'a boolean value (true)';
        }

        if (\is_string($var)) {
            return sprintf('a string ("%s%s")', mb_substr($var, 0, 255), mb_strlen($var) > 255 ? '...' : '');
        }

        if (is_numeric($var)) {
            return sprintf('a number (%s)', (string)$var);
        }

        return (string)$var;
    }
}