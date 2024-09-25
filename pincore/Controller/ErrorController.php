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
        $message = $this->getErrorMessage($statusCode);
        $class = $exception->getClass();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = nl2br($exception->getTraceAsString());

        // Construct a styled error message with improved visibility and custom messages
        $msg = <<<EOT
        <html>
        <head>
            <title>Error $statusCode</title>
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    background-color: #f8f9fa;
                    color: #212529;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    overflow: auto;
                }
                .container {
                    background-color: #fff;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    width: 100%;
                    max-width: 1200px;
                    overflow: hidden;
                    box-sizing: border-box;
                }
                .status-code {
                    font-size: 100px;
                    font-weight: bold;
                    color: #dc3545;
                    text-align: center;
                    margin-bottom: 10px;
                }
                h1 {
                    font-size: 24px;
                    color: #343a40;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .message {
                    font-size: 18px;
                    margin-bottom: 20px;
                    text-align: center;
                    color: #6c757d;
                }
                .details {
                    margin-top: 20px;
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-left: 5px solid #dc3545;
                    border-radius: 5px;
                    overflow: auto;
                    max-height: 400px;
                }
                .details p {
                    margin-bottom: 10px;
                    color: #495057;
                }
                .details .trace {
                    margin-top: 15px;
                    background-color: #e9ecef;
                    padding: 15px;
                    border-radius: 5px;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 14px;
                    color: #343a40;
                    white-space: pre-wrap;
                    overflow-x: auto;
                    max-height: 200px;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 14px;
                    color: #6c757d;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="status-code">$statusCode</div>
                <h1>$message</h1>
                <div class="details">
                    <p><strong>Line:</strong> $line</p>
                    <p><strong>Class:</strong> $class</p>
                    <p><strong>File:</strong> $file</p>
                    <h3>Trace:</h3>
                    <div class="trace">$trace</div>
                </div>
                <div class="footer">
                    &copy; <?= date('Y'); ?> PINOOX. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        EOT;

        // Create the response object with the detailed error message
        $response = new Response($msg, $statusCode);
        $response->setResponseError(true);

        return $response;
    }

    /**
     * Get a custom error message based on the status code.
     *
     * @param int $statusCode
     * @return string
     */
    private function getErrorMessage(int $statusCode): string
    {
        switch ($statusCode) {
            case 400:
                return 'Bad Request: The server could not understand the request due to invalid syntax.';
            case 401:
                return 'Unauthorized: Access is denied due to invalid credentials.';
            case 403:
                return 'Forbidden: You do not have permission to access this resource.';
            case 404:
                return 'Not Found: The requested resource could not be found on this server.';
            case 405:
                return 'Method Not Allowed: The request method is not supported for the requested resource.';
            case 500:
                return 'Internal Server Error: The server encountered an internal error and could not complete your request.';
            case 502:
                return 'Bad Gateway: The server received an invalid response from the upstream server.';
            case 503:
                return 'Service Unavailable: The server is currently unavailable (overloaded or down).';
            case 504:
                return 'Gateway Timeout: The server did not receive a timely response from the upstream server.';
            default:
                return 'An unexpected error occurred. Please try again later.';
        }
    }
}