<?php

namespace Pinoox\Component\Kernel;

use Pinoox\Component\Flow\FlowManager;
use Pinoox\Component\Router\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as ResponseSymfony;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Exception;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class Kernel extends HttpKernel
{
    const HANDLE_BEFORE = 'kernel.handle.before';
    const HANDLE_AFTER = 'kernel.handle.after';

    private ?FlowManager $flowManager = null;

    public function setFlowManager(FlowManager $flowManager): void
    {
        $this->flowManager = $flowManager;
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): ResponseSymfony
    {
        try {
            $event = new RequestEvent($this, $request, $type);
            $this->dispatcher->dispatch($event, self::HANDLE_BEFORE);
        } catch (\Throwable $e) {
            if (false === $catch) {
                throw $e;
            }
            return $this->handleThrowable($e, $request, $type);
        }


        $next = function ($request) use ($type, $catch) {
            return parent::handle($request, $type, $catch);
        };

        if ($type === HttpKernelInterface::MAIN_REQUEST && $this->flowManager !== null) {
            $this->addRouteFlows($request);
            $this->flowManager->setRequestEvent($event);
            $response = $this->flowManager->handle($request, $next);
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

    private function addRouteFlows(Request $request): void
    {
        $flows = $request->attributes->get('_flows');
        if (!empty($flows) && is_array($flows)) {
            $this->flowManager->addFlows($flows);
        }

        $router = $request->attributes->get('_router');
        $flows = !empty($router) && ($router instanceof Route) ? $router->flows : [];
        $this->flowManager->addFlows($flows);
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

    private function handleThrowable(\Throwable $e, Request $request, int $type): \Symfony\Component\HttpFoundation\Response
    {
        $event = new ExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        // a listener might have replaced the exception
        $e = $event->getThrowable();

        $response = $event->getResponse();

        // the developer asked for a specific status code
        if (!$event->isAllowingCustomResponseCode() && !$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            // ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } else {
                $response->setStatusCode(500);
            }
        }

        return $response;
    }

    public function getDispatcher(): \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}
