<?php

namespace Pinoox\Component\Kernel\Debug\Support;

use Closure;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Router\Route;
use Pinoox\Component\Router\RouteSourceRegistry;
use Symfony\Component\HttpFoundation\Request;

class RouteContextResolver
{
    public static function resolve(?Request $request = null): array
    {
        $request ??= self::currentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        $route = $request->attributes->get('_router');
        if (!$route instanceof Route) {
            return [];
        }

        $routeName = (string) ($request->attributes->get('_route') ?? $route->getName());
        $routeSource = RouteSourceRegistry::route($routeName);
        $declaredAction = $routeSource['declared'] ?? null;
        $handler = $route->getAction();
        $actionRef = self::extractActionReference($declaredAction, $handler, $routeSource);
        $actionSource = self::resolveActionSource($actionRef, $route, $handler);

        if ($actionSource === null && $handler instanceof Closure) {
            $actionSource = RouteSourceRegistry::closureLocation($handler);
        }

        if ($routeSource === null && $handler instanceof Closure) {
            $routeSource = RouteSourceRegistry::closureLocation($handler);
        }

        $methods = $route->getMethods();
        if (is_string($methods)) {
            $methods = [$methods];
        }

        return array_filter([
            'name' => $routeName !== '' ? $routeName : null,
            'path' => $route->getPath(),
            'methods' => array_values(array_filter((array) $methods)),
            'action_ref' => $actionRef,
            'handler' => RouteSourceRegistry::describeAction($handler),
            'route_source' => $routeSource,
            'action_source' => $actionSource,
            'flows' => $route->flows ?: null,
            'tags' => $route->tags ?: null,
        ], static fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    private static function currentRequest(): ?Request
    {
        if (!class_exists(\Pinoox\Portal\Kernel\HttpKernel::class)) {
            return null;
        }

        try {
            $stack = \Pinoox\Portal\Kernel\HttpKernel::requestStack();

            return $stack->getCurrentRequest();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function extractActionReference(?string $declaredAction, mixed $handler, ?array $routeSource): ?string
    {
        if (is_string($declaredAction) && $declaredAction !== '' && $declaredAction !== '{closure}') {
            return $declaredAction;
        }

        if (is_string($handler) && (str_starts_with($handler, '@') || str_starts_with($handler, '&'))) {
            return $handler;
        }

        if ($handler instanceof Closure) {
            return '{closure}';
        }

        if (is_array($handler)) {
            return RouteSourceRegistry::describeAction($handler);
        }

        return null;
    }

    private static function resolveActionSource(?string $actionRef, Route $route, mixed $handler): ?array
    {
        if ($actionRef === null || $actionRef === '{closure}') {
            return null;
        }

        if (!str_starts_with($actionRef, '@') && !str_starts_with($actionRef, '&')) {
            return null;
        }

        $prefix = str_starts_with($actionRef, '&') ? '&' : '@';
        $shortName = Str::firstDelete($actionRef, $prefix);
        $collectionPrefix = $route->getCollection()->name ?? '';

        $candidates = array_values(array_unique(array_filter([
            $collectionPrefix . $shortName,
            $shortName,
        ])));

        foreach ($candidates as $candidate) {
            $source = RouteSourceRegistry::action($candidate);
            if ($source !== null) {
                return $source;
            }
        }

        return null;
    }
}

