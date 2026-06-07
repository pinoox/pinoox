<?php

namespace Pinoox\Component\Kernel\Debug;

use Pinoox\Component\Kernel\Debug\Support\ExceptionContext;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\ErrorHandler\ErrorRenderer\CliErrorRenderer;

class PinooxDebug
{
    public static function enable(): ErrorHandler
    {
        error_reporting(\E_ALL & ~\E_DEPRECATED & ~\E_USER_DEPRECATED);

        if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
            ini_set('display_errors', 0);
        } elseif (!filter_var(\ini_get('log_errors'), \FILTER_VALIDATE_BOOL) || \ini_get('error_log')) {
            ini_set('display_errors', 1);
        }

        @ini_set('zend.assertions', 1);
        ini_set('assert.active', 1);
        ini_set('assert.exception', 1);

        DebugClassLoader::enable();

        $handler = ErrorHandler::register(new ErrorHandler(new BufferingLogger(), true));
        $projectDir = ExceptionContext::collect()['project_root'];

        $handler->setExceptionHandler(static function (\Throwable $exception) use ($handler, $projectDir): void {
            if (\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
                $renderer = new CliErrorRenderer();
            } else {
                $renderer = new PinooxHtmlErrorRenderer(true, null, null, $projectDir);
            }

            $exception = $renderer->render($exception);

            if (!headers_sent()) {
                http_response_code($exception->getStatusCode());

                foreach ($exception->getHeaders() as $name => $value) {
                    header($name . ': ' . $value, false);
                }
            }

            echo $exception->getAsString();
            exit(255);
        });

        return $handler;
    }
}

