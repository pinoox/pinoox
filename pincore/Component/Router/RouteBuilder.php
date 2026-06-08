<?php

namespace Pinoox\Component\Router;

use Closure;

class RouteBuilder
{
    private string $path = '/';
    private array|string|Closure $action = '';
    private string $name = '';
    private array $methods = [];
    private array $defaults = [];
    private array $filters = [];
    private ?int $priority = null;
    private array $data = [];
    private array $flows = [];
    private array $tags = [];
    private bool $registered = false;

    public function __construct(private readonly Router $router)
    {
    }

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function action(array|string|Closure $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function methods(array|string $methods): self
    {
        $this->methods = is_array($methods) ? $methods : [$methods];

        return $this;
    }

    public function method(string $method): self
    {
        return $this->methods([$method]);
    }

    public function get(): self
    {
        return $this->method('GET');
    }

    public function post(): self
    {
        return $this->method('POST');
    }

    public function put(): self
    {
        return $this->method('PUT');
    }

    public function patch(): self
    {
        return $this->method('PATCH');
    }

    public function delete(): self
    {
        return $this->method('DELETE');
    }

    public function options(): self
    {
        return $this->method('OPTIONS');
    }

    public function head(): self
    {
        return $this->method('HEAD');
    }

    public function defaults(array $defaults): self
    {
        $this->defaults = $defaults;

        return $this;
    }

    public function filters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function permission(string $permission): self
    {
        $this->data['permission'] = $permission;
        $this->flows = RouteManifest::withPermissionFlow($this->flows, $permission);

        return $this;
    }

    public function flows(array $flows): self
    {
        $this->flows = $flows;

        return $this;
    }

    public function themeContext(string $context): self
    {
        return $this->flow('theme.' . ltrim($context, '.'));
    }

    public function flow(array|string $flow): self
    {
        return $this->appendFlows($flow);
    }

    private function appendFlows(array|string $flows): self
    {
        $this->flows = array_values(array_unique(array_merge($this->flows, is_array($flows) ? $flows : [$flows])));

        return $this;
    }

    public function tags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function priority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function fixWebServer(bool $fix = true): self
    {
        $this->data['fix_web_server'] = $fix;

        return $this;
    }

    public function register(): Router
    {
        if ($this->registered) {
            return $this->router;
        }

        $this->registered = true;

        $this->router->add(
            $this->path,
            $this->action,
            $this->name,
            $this->methods,
            $this->defaults,
            $this->filters,
            $this->priority,
            $this->data,
            $this->flows,
            $this->tags,
        );

        return $this->router;
    }

    public function __destruct()
    {
        if (!$this->registered) {
            $this->register();
        }
    }
}

