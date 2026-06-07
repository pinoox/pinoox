<?php

namespace Pinoox\Component\Router\Action;

use Closure;
use Pinoox\Component\Router\Router;

class ActionBuilder
{
    private array|string|Closure|null $handler = null;
    private string $description = '';
    /** @var list<string> */
    private array $flows = [];
    /** @var list<string> */
    private array $tags = [];

    public function __construct(
        private readonly Router $router,
        private readonly string $name,
    ) {
    }

    public function handle(array|string|Closure $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function flow(array|string $flow): self
    {
        $flows = is_array($flow) ? $flow : [$flow];
        $this->flows = array_values(array_unique(array_merge($this->flows, $flows)));

        return $this;
    }

    public function tag(array|string $tag): self
    {
        $tags = is_array($tag) ? $tag : [$tag];
        $this->tags = array_values(array_unique(array_merge($this->tags, $tags)));

        return $this;
    }

    public function register(): Router
    {
        if ($this->handler === null) {
            throw new \InvalidArgumentException(sprintf('Action "%s" requires a handler. Call handle() first.', $this->name));
        }

        $this->router->registerNamedAction(
            $this->name,
            $this->handler,
            $this->description,
            $this->flows,
            $this->tags,
        );

        return $this->router;
    }
}

