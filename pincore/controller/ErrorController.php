<?php

namespace pinoox\controller;

use pinoox\component\Http\Request;
use pinoox\component\kernel\controller\Controller;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use pinoox\component\Http\Response;

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
