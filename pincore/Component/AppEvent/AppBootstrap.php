<?php

namespace Pinoox\Component\AppEvent;

use Pinoox\Component\Cache\Store\BootCacheStore;
use Pinoox\Component\Router\RouteManifest;
use Pinoox\Component\Router\Router;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Event;
use Pinoox\Portal\FlowManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AppBootstrap
{
    /** @var array<string, true> */

    private static array $booted = [];

    /** @var array<string, true> */

    private static array $integrated = [];

    /** @var array<string, AppRegisterCollector> */

    private static array $pendingIntegration = [];

    /** @var array<string, list<string>>|null */

    private static ?array $extenderIndex = null;

    private static bool $kernelReady = false;

    public static function markKernelReady(): void
    {
        self::$kernelReady = true;
    }

    public static function booted(string $package): bool
    {
        return isset(self::$booted[$package]);
    }

    /**
     * Boot every enabled app marked with boot-global in app.php (site-wide plugins).
     */
    public static function bootGlobalApps(bool $integrate = false): void
    {
        foreach (self::globalBootPackages() as $package) {
            if (self::booted($package)) {
                if ($integrate) {
                    self::integrate($package);
                }

                continue;
            }

            self::bootPackage($package, false, $integrate);
        }
    }

    /**
     * @return list<string>
     */
    public static function globalBootPackages(): array
    {
        if (self::$globalBootPackagesCache !== null) {
            return self::$globalBootPackagesCache;
        }

        $packages = [];

        foreach (AppEngine::all() as $package => $manager) {
            if (!$manager->stable()) {
                continue;
            }

            if ((bool) $manager->config()->get('boot-global')) {
                $packages[] = $package;
            }
        }

        sort($packages);

        return self::$globalBootPackagesCache = $packages;
    }

    /** @var list<string>|null */

    private static ?array $globalBootPackagesCache = null;

    /**
     * Reset in-memory boot state (tests only).
     */
    public static function resetState(): void
    {
        self::$booted = [];
        self::$integrated = [];
        self::$pendingIntegration = [];
        self::$extenderIndex = null;
        self::$globalBootPackagesCache = null;
        self::$kernelReady = false;
        AppRegisterCollector::$pendingWhen = [];
    }

    /**
     * @param bool $integrate When true, wires flows/listeners and fires lifecycle events (safe after kernel boot).
     */
    public static function ensure(?string $package = null, bool $integrate = false): AppRegister
    {
        $package = $package ?? (string) (App::package() ?? '');
        if ($package === '') {
            return new AppRegister('', new AppRegisterCollector());
        }

        if (self::booted($package)) {
            if ($integrate) {
                self::integrate($package);
            }

            return new AppRegister($package, new AppRegisterCollector());
        }

        if (BootCacheStore::tryHydrate($package)) {
            self::$booted[$package] = true;
            if ($integrate) {
                self::integrate($package);
            }

            return new AppRegister($package, new AppRegisterCollector());
        }

        self::bootExtendersFor($package, $integrate);

        return self::bootPackage($package, false, $integrate);
    }

    public static function applyRoutes(string $package, Router $router, bool $dispatchEvents = true): void
    {
        self::ensure($package, false);

        AppRouteRegistry::applyActions($package, $router);
        AppRouteRegistry::applyWeb($package, $router);

        if (!$dispatchEvents || !self::canDispatch()) {
            return;
        }

        $register = new AppRegister($package, new AppRegisterCollector());
        $event = new AppRoutesEvent($package, $router, $register);
        Event::dispatch($event, AppEventNames::ROUTES);
        Event::dispatch($event, AppEventNames::package(AppEventNames::ROUTES, $package));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function apiManifests(string $package): array
    {
        self::ensure($package, false);

        $manifests = AppApiRegistryStore::manifests($package);
        $routes = AppApiRegistryStore::routes($package);

        if ($routes === []) {
            return $manifests;
        }

        $merged = $manifests !== [] ? $manifests : [[
            'version' => 'v1',
            'prefix' => '',
            'routes' => [],
        ]];

        foreach ($merged as &$manifest) {
            foreach ($routes as $route) {
                if (isset($route['_version']) && ($manifest['version'] ?? 'v1') !== $route['_version']) {
                    continue;
                }

                unset($route['_version']);
                $manifest['routes'][] = RouteManifest::normalizeEntry($route, [], [], forApi: true);
            }
        }
        unset($manifest);

        return $merged;
    }

    public static function integrate(string $package): void
    {
        if (isset(self::$integrated[$package])) {
            return;
        }

        if (isset(self::$pendingIntegration[$package])) {
            self::applyIntegration($package, self::$pendingIntegration[$package]);
            unset(self::$pendingIntegration[$package]);
        }

        if (self::canDispatch()) {
            $register = new AppRegister($package, new AppRegisterCollector());
            $booted = new AppBootEvent($package, $register, false);
            Event::dispatch($booted, AppEventNames::BOOTED);
            Event::dispatch($booted, AppEventNames::package(AppEventNames::BOOTED, $package));

            $api = new AppApiEvent($package, $register);
            Event::dispatch($api, AppEventNames::API);
            Event::dispatch($api, AppEventNames::package(AppEventNames::API, $package));
        }

        self::$integrated[$package] = true;
    }

    private static function bootExtendersFor(string $targetPackage, bool $integrate): void
    {
        foreach (self::extendersFor($targetPackage) as $extender) {
            if (!self::booted($extender)) {
                self::bootPackage($extender, true, $integrate);
            }
        }
    }

    private static function bootPackage(string $package, bool $asExtender, bool $integrate): AppRegister
    {
        if (self::booted($package)) {
            return new AppRegister($package, new AppRegisterCollector());
        }

        $collector = new AppRegisterCollector();
        $register = new AppRegister($package, $collector);

        if ($integrate && self::canDispatch()) {
            $booting = new AppBootEvent($package, $register, $asExtender);
            Event::dispatch($booting, AppEventNames::BOOTING);
            Event::dispatch($booting, AppEventNames::package(AppEventNames::BOOTING, $package));
        }

        self::runBootFile($package, $register);
        self::runStartup($package, $register);
        self::applyWhenTargets($package, $register);

        self::commit($package, $collector, $integrate);

        self::$booted[$package] = true;

        if ($integrate) {
            self::integrate($package);
        }

        return $register;
    }

    private static function runBootFile(string $package, AppRegister $register): void
    {
        $path = self::bootPath($package);
        if ($path === null) {
            return;
        }

        $boot = include $path;
        if (is_callable($boot)) {
            $boot($register);
        }
    }

    private static function runStartup(string $package, AppRegister $register): void
    {
        try {
            $startup = AppEngine::config($package)->get('startup');
        } catch (\Throwable) {
            return;
        }

        if (is_callable($startup)) {
            $startup($register);
        }
    }

    private static function bootPath(string $package): ?string
    {
        try {
            $config = AppEngine::config($package)->get('boot');
        } catch (\Throwable) {
            $config = null;
        }

        if ($config === false) {
            return null;
        }

        if (is_string($config) && $config !== '') {
            $path = AppEngine::path($package, $config);

            return is_file($path) ? $path : null;
        }

        $default = AppEngine::path($package, 'boot.php');

        return is_file($default) ? $default : null;
    }

    private static function applyWhenTargets(string $package, AppRegister $register): void
    {
        $callbacks = AppRegisterCollector::$pendingWhen[$package] ?? [];
        unset(AppRegisterCollector::$pendingWhen[$package]);

        foreach ($callbacks as $callback) {
            $callback($register);
            self::commit($package, $register->collector(), false);
        }
    }

    private static function commit(string $package, AppRegisterCollector $collector, bool $integrate): void
    {
        AppRouteRegistry::absorb($package, $collector);
        AppApiRegistryStore::absorb($package, $collector);
        AppGraphQLRegistryStore::absorb($package, $collector);
        AppScheduleRegistryStore::absorb($package, $collector);

        if ($integrate) {
            self::applyIntegration($package, $collector);
        } elseif ($collector->aliases !== [] || $collector->flows !== [] || $collector->listeners !== [] || $collector->subscribers !== []) {
            self::$pendingIntegration[$package] = self::mergePending($package, $collector);
        }
    }

    private static function mergePending(string $package, AppRegisterCollector $collector): AppRegisterCollector
    {
        $pending = self::$pendingIntegration[$package] ?? new AppRegisterCollector();

        $pending->flows = array_merge($pending->flows, $collector->flows);
        $pending->aliases = array_replace_recursive($pending->aliases, $collector->aliases);
        $pending->listeners = array_merge($pending->listeners, $collector->listeners);
        $pending->subscribers = array_merge($pending->subscribers, $collector->subscribers);

        return $pending;
    }

    private static function applyIntegration(string $package, AppRegisterCollector $collector): void
    {
        if ($collector->aliases !== []) {
            FlowManager::addAliases($collector->aliases);
        }

        if ($collector->flows !== []) {
            FlowManager::addAliases($collector->flows);
        }

        if (!self::canDispatch()) {
            return;
        }

        foreach ($collector->listeners as [$event, $listener, $priority]) {
            Event::listen($event, $listener, $priority);
        }

        foreach ($collector->subscribers as $subscriber) {
            if (is_subclass_of($subscriber, EventSubscriberInterface::class)) {
                Event::addSubscriber(new $subscriber());
            }
        }
    }

    private static function canDispatch(): bool
    {
        return self::$kernelReady;
    }

    /**
     * @return list<string>
     */
    private static function extendersFor(string $targetPackage): array
    {
        return self::extenderIndex()[$targetPackage] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function extenderIndex(): array
    {
        if (self::$extenderIndex !== null) {
            return self::$extenderIndex;
        }

        $index = [];

        foreach (AppEngine::all() as $package => $manager) {
            if (!$manager->stable()) {
                continue;
            }

            $extends = $manager->config()->get('extends') ?? [];
            if (!is_array($extends)) {
                continue;
            }

            foreach ($extends as $target) {
                if (!is_string($target) || $target === '') {
                    continue;
                }

                $index[$target][] = $package;
            }
        }

        self::$extenderIndex = $index;

        return $index;
    }
}

