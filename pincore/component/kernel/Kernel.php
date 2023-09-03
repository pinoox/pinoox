<?php

namespace pinoox\component\kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;
use Exception;

class Kernel extends HttpKernel
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function handleSubRequest(Request $request): Response
    {
        return parent::handle($request, self::SUB_REQUEST,false);
    }
}
