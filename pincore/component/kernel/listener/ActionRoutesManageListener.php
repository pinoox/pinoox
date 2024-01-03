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

use Pinoox\Component\Helpers\HelperString;
use Pinoox\Portal\App\App;
use Pinoox\Component\Router\Route;
use Pinoox\Portal\Router;
use Pinoox\Component\Router\Collection;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ActionRoutesManageListener implements EventSubscriberInterface
{
    public function onActionRoutesManage(RequestEvent $event)
    {
        $this->buildAction($event);
        $this->buildCollectionAction($event);
        $this->setActionRouter($event);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onActionRoutesManage'];
    }

    private function buildAction(RequestEvent $event)
    {
        if ($event->getRequest()->attributes->has('_controller')) {
            $controller = $event->getRequest()->attributes->get('_controller');
            $action = $this->buildValueAction($event, $controller);
            $event->getRequest()->attributes->set('_controller', $action);
        }
    }

    private function setActionRouter(RequestEvent $event)
    {
        if ($event->getRequest()->attributes->has('_controller')) {
            $router = $event->getRequest()->attributes->get('_router');
            if ($router instanceof Route) {
                $router->setAction($event->getRequest()->attributes->get('_controller'));
            }

            $event->getRequest()->attributes->set('_router', $router);
        }
    }

    private function buildValueAction(RequestEvent $event, $controller)
    {
        $action = $controller;
        if (is_string($controller) && HelperString::firstHas($controller, '@')) {
            $controller = HelperString::firstDelete($controller, '@');
            if ($controller = Router::getAction($controller)) {
                $action = $controller;
            } else {
                throw new BadMethodCallException('"' . $action . '" action method is not found in ' . App::package() . ' app');
            }
        }

        return $this->getCollection($event)->buildAction($action);
    }

    private function getCollection(RequestEvent $event): Collection
    {
        if ($event->getRequest()->attributes->has('_router'))
            return $event->getRequest()->attributes->get('_router')->getCollection();
        else
            return Router::getCollection();
    }

    private function buildCollectionAction(RequestEvent $event)
    {
        if ($event->getRequest()->attributes->has('_action_collection')) {
            $request = clone $event->getRequest();
            $controller = $request->attributes->get('_action_collection');
            $action = $this->buildValueAction($event, $controller);
            $request->attributes->set('_controller', $action);
            $request->attributes->remove('_action_collection');
            $event->getRequest()->attributes->remove('_action_collection');
            $response = $event->getKernel()->handleSubRequest($request);
            $response->send();
        }
    }
}