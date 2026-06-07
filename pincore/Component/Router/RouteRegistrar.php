<?php

namespace Pinoox\Component\Router;

use Closure;
use Pinoox\Portal\Router as RouterPortal;

class RouteRegistrar
{
    private static ?RouteRegister $context = null;

    public function get(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->register()->get($path, $action);
    }

    public function post(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->register()->post($path, $action);
    }

    public function put(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->register()->put($path, $action);
    }

    public function patch(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->register()->patch($path, $action);
    }

    public function delete(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->register()->delete($path, $action);
    }

    public function match(array|string $methods, string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->register()->match($methods, $path, $action);
    }

    /**
     * @param array{
     *     prefix?: string,
     *     name?: string,
     *     as?: string,
     *     flow?: string|list<string>,
     *     flows?: list<string>,
     *     middleware?: string|list<string>,
     *     tags?: list<string>,
     *     defaults?: array<string, mixed>,
     *     filters?: array<string, string>,
     *     data?: array<string, mixed>,
     * } $attributes
     */
    public function group(array $attributes, callable $callback): void
    {
        $router = $this->router();

        $router->collection(
            path: (string) ($attributes['prefix'] ?? ''),
            routes: $callback,
            prefixName: (string) ($attributes['name'] ?? $attributes['as'] ?? ''),
            flows: $this->flows($attributes),
            tags: $attributes['tags'] ?? [],
            defaults: $attributes['defaults'] ?? [],
            filters: $attributes['filters'] ?? [],
            data: $attributes['data'] ?? [],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function collect(callable $callback): array
    {
        return RouteRegister::collect(function (RouteRegister $register) use ($callback) {
            $previous = self::$context;
            self::$context = $register;

            try {
                $callback();
            } finally {
                self::$context = $previous;
            }
        });
    }

    private function register(): RouteRegister
    {
        if (self::$context !== null) {
            return self::$context;
        }

        return new RouteRegister($this->router());
    }

    private function router(): Router
    {
        try {
            return RouterPortal::___();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Route definitions require an active router context.', 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     * @return list<string>
     */
    private function flows(array $attributes): array
    {
        $flows = $attributes['flows']
            ?? $attributes['flow']
            ?? $attributes['middleware']
            ?? [];

        if (is_string($flows)) {
            return [$flows];
        }

        return is_array($flows) ? array_values($flows) : [];
    }
}

