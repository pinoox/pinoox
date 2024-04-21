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
        $controller = $event->getController()[0] ?? $event->getController();
        $reflectionClass = new \ReflectionClass($controller);

        if ($controller instanceof Transactional || $reflectionClass->getAttributes(Transactional::class)) {
            $this->db?->getConnection()->beginTransaction();
            $event->getRequest()->attributes->set('transactional', true);
            return;
        }

        $method = $event->getController()[1] ?? null;
        if ($method) {
            $reflectionMethod = new \ReflectionMethod($controller, $method);
            if ($reflectionMethod->getAttributes(Transactional::class)) {
                $this->db?->getConnection()->beginTransaction();
                $event->getRequest()->attributes->set('transactional', true);
            }
        }
    }

    public function onView(ViewEvent $event)
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
            KernelEvents::VIEW => 'onView',
            KernelEvents::EXCEPTION => ['onException', -1],
        );
    }
}