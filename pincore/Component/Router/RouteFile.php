<?php

namespace Pinoox\Component\Router;

class RouteFile
{
    public function __construct(private readonly ?Router $router = null)
    {
    }

    /**
     * @param callable(RouteRegister): void $callback
     */
    public function register(callable $callback): void
    {
        if ($this->router === null) {
            throw new \RuntimeException('Route registration requires an active router context.');
        }

        $callback(new RouteRegister($this->router));
    }

    /**
     * @param callable(RouteRegister): void $callback
     * @return list<array<string, mixed>>
     */
    public function collect(callable $callback): array
    {
        return RouteRegister::collect($callback);
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, mixed>
     */
    public function manifest(array $manifest): array
    {
        return RouteManifest::normalizeManifest($manifest);
    }
}

