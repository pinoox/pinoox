<?php

namespace Pinoox\Component\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Exception;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Kernel extends HttpKernel
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function handleSubRequest(Request $request): Response
    {
        return static::handle($request, static::SUB_REQUEST,false);
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }
}
