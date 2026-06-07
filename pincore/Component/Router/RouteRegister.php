<?php

namespace Pinoox\Component\Router;

use Closure;

class RouteRegister
{
    /** @var list<array<string, mixed>> */
    private array $entries = [];

    public function __construct(private readonly ?Router $router = null)
    {
    }

    /**
     * @param callable(self): void $callback
     * @return list<array<string, mixed>>
     */
    public static function collect(callable $callback): array
    {
        $register = new self(null);
        $callback($register);

        return $register->entries;
    }

    public function route(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->method('GET', $path, $action);
    }

    public function get(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->method('GET', $path, $action);
    }

    public function post(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->method('POST', $path, $action);
    }

    public function put(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->method('PUT', $path, $action);
    }

    public function patch(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->method('PATCH', $path, $action);
    }

    public function delete(string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        return $this->method('DELETE', $path, $action);
    }

    public function match(array|string $methods, string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        if ($this->router !== null) {
            return $this->router->route($path, $action)->methods($methods);
        }

        $builder = new RouteEntryBuilder($this, 'GET', $path, $action);
        $builder->methods($methods);

        return $builder;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function pushEntry(array $entry): void
    {
        $this->entries[] = $entry;
    }

    private function method(string $method, string $path, array|string|Closure $action = ''): RouteBuilder|RouteEntryBuilder
    {
        if ($this->router !== null) {
            return match (strtoupper($method)) {
                'POST' => $this->router->route($path, $action)->post(),
                'PUT' => $this->router->route($path, $action)->put(),
                'PATCH' => $this->router->route($path, $action)->patch(),
                'DELETE' => $this->router->route($path, $action)->delete(),
                default => $this->router->route($path, $action)->get(),
            };
        }

        return new RouteEntryBuilder($this, $method, $path, $action);
    }
}

