<?php

namespace Pinoox\Component\Router\Action;

use Pinoox\Component\Http\Request;
use Pinoox\Component\Router\Router;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class ActionInvoker
{
    public static function invoke(Router $router, string $actionName, ?Request $request = null, ?string $collectionPrefix = ''): mixed
    {
        $handler = $router->resolveAction($actionName, $collectionPrefix ?? '');
        if ($handler === false) {
            throw new ActionValidationException([
                sprintf('Action "%s" could not be resolved.', $actionName),
            ]);
        }

        $built = $router->buildAction($handler);

        if (is_callable($built)) {
            return $built($request ?? Request::createFromGlobals());
        }

        if (is_array($built) && count($built) === 2) {
            [$class, $method] = $built;
            $instance = is_object($class) ? $class : new $class();
            $request ??= Request::createFromGlobals();

            return $instance->{$method}($request);
        }

        if (is_string($built) && class_exists($built)) {
            $instance = new $built();
            if (is_callable($instance)) {
                return $instance($request ?? Request::createFromGlobals());
            }
        }

        throw new ActionValidationException([
            sprintf('Action "%s" resolved to a non-invokable handler.', $actionName),
        ]);
    }

    public static function createRequest(string $method = 'GET', string $uri = '/', array $parameters = []): Request
    {
        $symfony = SymfonyRequest::create($uri, $method, $parameters);

        return Request::createFromBase($symfony);
    }
}

