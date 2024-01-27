<?php

namespace Pinoox\Controller;

use Pinoox\Component\Kernel\Controller\Controller;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Pinoox\Component\Http\Response;

class ErrorController extends Controller
{

    public function exception(FlattenException $exception)
    {
        $msg = 'Something went wrong! (' . $exception->getMessage() . ')';

        $response = new Response($msg, $exception->getStatusCode());
        $response->setResponseError(true);
        return $response;
    }
}
