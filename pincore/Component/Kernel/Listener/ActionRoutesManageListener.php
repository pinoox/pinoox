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

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Router\Action\ActionReference;
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
        if (is_string($controller)) {
            /**
             * @var Route $route
             */

            $route = $event->getRequest()->attributes->get('_router');
            $collectionPrefix = $route instanceof Route ? $route->getCollection()->name : '';

            if (ActionReference::isReference($controller)) {
                $resolved = App::router()->resolveAction($controller, $collectionPrefix);
                if ($resolved !== false) {
                    $action = $resolved;
                    $this->mergeActionFlows($event, $controller, $collectionPrefix);
                }
            }
        }

        return $this->getCollection($event)->buildAction($action);
    }

    private function mergeActionFlows(RequestEvent $event, string $reference, string $collectionPrefix): void
    {
        $resolvedKey = ActionReference::resolveKey(
            $reference,
            $collectionPrefix,
            array_keys(App::router()->actions),
        );

        if ($resolvedKey === null) {
            return;
        }

        $actionFlows = App::router()->actionFlows($resolvedKey);
        if ($actionFlows === []) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_router');
        if (!$route instanceof Route) {
            return;
        }

        $route->flows = array_values(array_unique(array_merge($route->flows, $actionFlows)));
    }

    private function getCollection(RequestEvent $event): Collection
    {
        if ($event->getRequest()->attributes->has('_router'))
            return $event->getRequest()->attributes->get('_router')->getCollection();
        else
            return App::router()->getCollection();
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
            $response = $event->getKernel()->handle($request,-1);
            $response->send();
        }
    }
}