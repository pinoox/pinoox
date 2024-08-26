<?php

namespace Pinoox\Controller;

use Pinoox\Component\Kernel\Controller\Controller;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Pinoox\Component\Http\Response;

class ErrorController extends Controller
{
    public function exception(FlattenException $exception)
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();

        // Construct a detailed error message
        $msg = <<<EOT
                Something went wrong!
                Message: $message
                File: $file
                Line: $line
                Status Code: $statusCode
                Trace: 
                $trace
                EOT;

        // Create the response object with the detailed error message
        $response = new Response($msg, $statusCode);
        $response->setResponseError(true);

        return $response;
    }
}
