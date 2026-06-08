<?php

namespace Pinoox\Component\Router;

use Closure;

class RouteEntryBuilder
{
    private string $path = '/';
    private array|string|Closure $action = '';
    private string $name = '';
    /** @var list<string> */
    private array $methods = ['GET'];
    private array $defaults = [];
    private array $filters = [];
    private ?int $priority = null;
    private array $data = [];
    private array $flows = [];
    private array $tags = [];
    private array $apiMeta = [];
    private bool $registered = false;

    public function __construct(
        private readonly RouteRegister $register,
        string $method,
        string $path,
        array|string|Closure $action = '',
    ) {
        $this->methods = [strtoupper($method)];
        $this->path = $path;
        $this->action = $action;
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

    public function flows(array $flows): self
    {
        $this->flows = $flows;

        return $this;
    }

    public function flow(array|string $flow): self
    {
        $this->flows = array_values(array_unique(array_merge(
            $this->flows,
            is_array($flow) ? $flow : [$flow],
        )));

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

    public function tag(string $tag): self
    {
        $this->apiMeta['tag'] = $tag;

        return $this;
    }

    public function summary(string $summary): self
    {
        $this->apiMeta['summary'] = $summary;

        return $this;
    }

    public function description(string $description): self
    {
        $this->apiMeta['description'] = $description;

        return $this;
    }

    public function params(array $params): self
    {
        $this->apiMeta['params'] = $params;

        return $this;
    }

    public function body(array $body): self
    {
        $this->apiMeta['body'] = $body;

        return $this;
    }

    public function response(array $response): self
    {
        $this->apiMeta['response'] = $response;

        return $this;
    }

    public function permission(string $permission): self
    {
        $this->data['permission'] = $permission;
        $this->flows = RouteManifest::withPermissionFlow($this->flows, $permission);

        return $this;
    }

    public function fixWebServer(bool $fix = true): self
    {
        $this->data['fix_web_server'] = $fix;

        return $this;
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        $this->register->pushEntry(array_merge(
            RouteManifest::normalizeEntry([
                'path' => $this->path,
                'action' => $this->action,
                'name' => $this->name,
                'methods' => $this->methods,
                'defaults' => $this->defaults,
                'filters' => $this->filters,
                'data' => $this->data,
                'flow' => $this->flows,
                'tags' => $this->tags,
                'priority' => $this->priority,
            ], forApi: true),
            $this->apiMeta,
        ));
    }

    public function __destruct()
    {
        if (!$this->registered) {
            $this->register();
        }
    }
}

