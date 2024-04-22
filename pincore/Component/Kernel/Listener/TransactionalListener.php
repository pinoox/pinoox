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


use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Database\Transactional;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TransactionalListener implements EventSubscriberInterface
{
    private ?DatabaseManager $db;

    public function __construct(?DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function onController(ControllerEvent $event)
    {
        $controller = $event->getController();
        if ($this->hasTransactional($controller)) {
            $this->db?->getConnection()->beginTransaction();
            $event->getRequest()->attributes->set('transactional', true);
        }
    }

    private function hasTransactional($controller): bool
    {
        if ($controller instanceof \Closure) {
            if ($this->hasTransactionalAttribute(new \ReflectionFunction($controller)))
                return true;
        }

        if (is_array($controller)) {
            $class = $controller[0] ?? $controller;

            if ($this->hasTransactionalAttribute(new \ReflectionClass($class)))
                return true;

            $method = $controller[1] ?? null;

            if ($method && $this->hasTransactionalAttribute(new \ReflectionMethod($class, $method)))
                return true;
        }

        return false;
    }

    private function hasTransactionalAttribute(\ReflectionClass|\ReflectionFunction|\ReflectionMethod $reflection): bool
    {
        return (bool)$reflection->getAttributes(Transactional::class);
    }

    public function onResponse(ResponseEvent $event)
    {
        if ($event->getRequest()->attributes->get('transactional')) {
            $response = $event->getResponse();
            if ($response && $response->getStatusCode() >= 400) {
                $this->db?->getConnection()->rollBack();
            } else {
                $this->db?->getConnection()->commit();
            }
        }
    }

    public function onException(ExceptionEvent $event)
    {
        if ($event->getRequest()->attributes->get('transactional')) {
            $this->db?->getConnection()->rollBack();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onController',
            KernelEvents::RESPONSE => 'onResponse',
            KernelEvents::EXCEPTION => 'onException',
        );
    }
}