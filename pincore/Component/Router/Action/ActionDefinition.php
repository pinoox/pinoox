<?php

namespace Pinoox\Component\Router\Action;

use Closure;

class ActionDefinition
{
    /** @var list<string> */
    private array $routeNames = [];

    public function __construct(
        public readonly string $name,
        public readonly mixed $handler,
        public readonly string $declared = '',
        public readonly string $description = '',
        /** @var list<string> */
        public readonly array $flows = [],
        /** @var list<string> */
        public readonly array $tags = [],
        public readonly ?string $file = null,
        public readonly ?int $line = null,
        public readonly ?string $relativeFile = null,
        public readonly ?string $group = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $handlerRef = null,
    ) {
    }

    public function addRoute(string $routeName, string $path): self
    {
        if (!in_array($routeName, $this->routeNames, true)) {
            $this->routeNames[] = $routeName;
        }

        return $this;
    }

    /** @return list<string> */

    public function routeNames(): array
    {
        return $this->routeNames;
    }

    public function handlerLabel(): string
    {
        if ($this->handlerRef !== null) {
            return ActionHandlerRef::label($this->handlerRef, $this->declared);
        }

        if ($this->declared !== '') {
            return $this->declared;
        }

        if ($this->handler instanceof Closure) {
            return '{closure}';
        }

        if (is_array($this->handler)) {
            return implode('::', array_map(static fn ($part) => is_string($part) ? $part : get_debug_type($part), $this->handler));
        }

        return is_string($this->handler) ? $this->handler : get_debug_type($this->handler);
    }

    public function isUsed(): bool
    {
        return $this->routeNames !== [];
    }

    public function isCacheable(): bool
    {
        return $this->handlerRef !== null;
    }

    /** @return array<string, mixed> */

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'handler' => $this->handlerLabel(),
            'handler_ref' => $this->handlerRef,
            'cacheable' => $this->isCacheable(),
            'description' => $this->description,
            'flows' => $this->flows,
            'tags' => $this->tags,
            'file' => $this->relativeFile ?? $this->file,
            'line' => $this->line,
            'group' => $this->group,
            'routes' => $this->routeNames,
            'used' => $this->isUsed(),
        ];
    }
}

