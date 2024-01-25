<?php

namespace Pinoox\Component\Kernel;

use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Service\ServiceManager;
use Pinoox\Component\Router\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as ResponseSymfony;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Exception;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class Kernel extends HttpKernel
{
    const HANDLE_BEFORE = 'handle.before';
    const HANDLE_AFTER = 'handle.after';

    private ?ServiceManager $serviceManager = null;

    public function setServiceManager(ServiceManager $serviceManager): void
    {
        $this->serviceManager = $serviceManager;
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): ResponseSymfony
    {
        $event = new RequestEvent($this, $request, $type);
        $this->dispatcher->dispatch($event, self::HANDLE_BEFORE);

        $next = function ($request) use ($type, $catch) {
            return parent::handle($request, $type, $catch);
        };

        if ($this->serviceManager !== null) {
            $this->addRouteServices($request);
            $this->serviceManager->setRequestEvent($event);
            $response = $this->serviceManager->handle($request, $next);
            if (!($response instanceof ResponseSymfony)) {
                $event = new ViewEvent($this, $request, $type, $response);
                $this->dispatcher->dispatch($event, self::HANDLE_AFTER);
                $response = $event->getResponse();
            }
        } else {
            $response = $next($request);
        }

        return $response;
    }

    private function addRouteServices(Request $request) : void
    {
        $router = $request->attributes->get('_router');
        $services = !empty($router) && ($router instanceof Route)? $router->services : [];
        $this->serviceManager->addServices($services);
    }

    /**
     * @param Request $request
     * @return ResponseSymfony
     * @throws Exception
     */
    public function handleSubRequest(Request $request): ResponseSymfony
    {
        return static::handle($request, static::SUB_REQUEST, false);
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }
}
