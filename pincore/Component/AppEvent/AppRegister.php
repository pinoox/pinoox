<?php

namespace Pinoox\Component\AppEvent;

use Closure;
use Pinoox\Component\Router\RouteManifest;
use Pinoox\Component\Router\Router;
use Pinoox\Portal\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fluent registration API used inside boot.php and event listeners.
 */
class AppRegister
{
    public function __construct(
        private readonly string $package,
        private readonly AppRegisterCollector $collector,
    ) {
    }

    public function package(): string
    {
        return $this->package;
    }

    public function web(callable $callback): self
    {
        $this->collector->webCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param array<string, mixed> $route
     */
    public function route(array $route): self
    {
        return $this->web(function (Router $router) use ($route): void {
            RouteManifest::apply($router, ['routes' => [$route]]);
        });
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public function api(array $manifest): self
    {
        $this->collector->apiManifests[] = RouteManifest::normalizeManifest($manifest);

        return $this;
    }

    /**
     * @param array<string, mixed> $route
     */
    public function apiRoute(array $route, ?string $version = null): self
    {
        $entry = $route;
        if ($version !== null) {
            $entry['_version'] = $version;
        }

        $this->collector->apiRoutes[] = $entry;

        return $this;
    }

    /**
     * Register flow middleware aliases for routes (maps alias name → Flow class).
     *
     * @param array<string, string|class-string> $map
     */
    public function flowAlias(array $map): self
    {
        $this->collector->flows = array_merge($this->collector->flows, $map);

        return $this;
    }

    /**
     * @param array<string, mixed> $aliases
     */
    public function alias(array $aliases): self
    {
        $this->collector->aliases = array_replace_recursive($this->collector->aliases, $aliases);

        return $this;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public function graphql(array $manifest): self
    {
        $this->collector->graphqlManifests[] = $manifest;

        return $this;
    }

    public function action(string $name, array|string|Closure $handler): self
    {
        $this->collector->actions[$name] = $handler;

        return $this;
    }

    public function schedule(callable $callback): self
    {
        $this->collector->schedules[] = $callback;

        return $this;
    }

    public function listen(string $event, callable|array $listener, int $priority = 0): self
    {
        $this->collector->listeners[] = [$event, $listener, $priority];

        return $this;
    }

    /**
     * @param class-string<EventSubscriberInterface> $subscriber
     */
    public function subscribe(string $subscriber): self
    {
        $this->collector->subscribers[] = $subscriber;

        return $this;
    }

    /**
     * Register routes/API when another app boots (plugin → host app).
     */
    public function when(string $targetPackage, callable $callback): self
    {
        AppRegisterCollector::$pendingWhen[$targetPackage][] = $callback;

        return $this;
    }

    public function collector(): AppRegisterCollector
    {
        return $this->collector;
    }
}

