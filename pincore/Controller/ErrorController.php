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
        $trace = nl2br($exception->getTraceAsString());

        // Construct a styled error message
        $msg = <<<EOT
        <html>
        <head>
            <title>Error $statusCode</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .container {
                    background-color: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    max-width: 600px;
                    width: 100%;
                    box-sizing: border-box;
                }
                h1 {
                    font-size: 24px;
                    color: #d9534f;
                    margin-bottom: 20px;
                    text-align: center;
                }
                p {
                    margin: 0 0 10px;
                }
                .trace {
                    background-color: #f7f7f9;
                    border-left: 5px solid #d9534f;
                    padding: 10px;
                    overflow-x: auto;
                }
                .file {
                    color: #555;
                    font-weight: bold;
                }
                .line {
                    color: #555;
                }
                .status-code {
                    text-align: center;
                    font-size: 48px;
                    color: #d9534f;
                    margin: 0 0 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="status-code">$statusCode</div>
                <h1>Something went wrong!</h1>
                <p class="message"><strong>Message:</strong> $message</p>
                <p class="file"><strong>File:</strong> $file</p>
                <p class="line"><strong>Line:</strong> $line</p>
                <h3>Trace:</h3>
                <div class="trace">$trace</div>
            </div>
        </body>
        </html>
        EOT;

        // Create the response object with the detailed error message
        $response = new Response($msg, $statusCode);
        $response->setResponseError(true);

        return $response;
    }
}
