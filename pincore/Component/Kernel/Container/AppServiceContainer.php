<?php

namespace Pinoox\Component\Kernel\Container;

use Pinoox\Component\Kernel\ContainerBuilder;
use Pinoox\Portal\App\AppEngine;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;

class AppServiceContainer
{
    /** @var array<string, array<string, mixed>> */

    private static array $compiled = [];

    public static function register(ContainerBuilder $builder, string $package): void
    {
        $config = self::config($package);
        $bindings = self::resolveBindings($package, $config);

        IlluminateBridge::applyBindings($bindings, $config['singletons'] ?? []);

        foreach ($bindings as $abstract => $concrete) {
            if (!is_string($abstract) || !is_string($concrete)) {
                continue;
            }

            IlluminateBridge::bind($abstract, $concrete);
        }

        if (!empty($config['autowire_controllers'])) {
            self::registerControllers($builder, $package);
        }

        self::$compiled[$package] = [
            'bindings' => $bindings,
            'controllers' => self::discoverControllers($package),
            'singletons' => is_array($config['singletons'] ?? null) ? array_keys($config['singletons']) : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(string $package): array
    {
        try {
            $config = AppEngine::config($package)->get('container');
        } catch (\Throwable) {
            $config = [];
        }

        return is_array($config) ? $config : [];
    }

    /**
     * @return array<string, string>
     */
    public static function resolveBindings(string $package, array $config): array
    {
        $bindings = is_array($config['bindings'] ?? null) ? $config['bindings'] : [];

        $file = AppEngine::path($package, 'bindings.php');
        if (is_file($file)) {
            $fileBindings = require $file;
            if (is_array($fileBindings)) {
                $bindings = array_merge($bindings, $fileBindings);
            }
        }

        return $bindings;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function export(string $package): ?array
    {
        return self::$compiled[$package] ?? null;
    }

    public static function hydrate(string $package, array $payload): void
    {
        $bindings = is_array($payload['bindings'] ?? null) ? $payload['bindings'] : [];
        IlluminateBridge::applyBindings($bindings, array_fill_keys($payload['singletons'] ?? [], true));

        self::$compiled[$package] = $payload;
    }

    private static function registerControllers(ContainerBuilder $builder, string $package): void
    {
        foreach (self::discoverControllers($package) as $class) {
            if ($builder->has($class)) {
                continue;
            }

            $definition = new Definition($class);
            $definition->setPublic(true);
            $definition->setAutowired(false);
            $builder->setDefinition($class, $definition);
        }
    }

    /**
     * @return list<string>
     */
    public static function discoverControllers(string $package): array
    {
        $controllerPath = AppEngine::path($package, 'Controller');
        if (!is_dir($controllerPath)) {
            return [];
        }

        $namespace = 'App\\' . $package . '\\Controller\\';
        $classes = [];

        $finder = new Finder();
        $finder->files()->in($controllerPath)->name('*.php');

        foreach ($finder as $file) {
            $relative = str_replace(['/', '\\'], '\\', $file->getRelativePathname());
            $class = $namespace . str_replace('.php', '', $relative);
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }
}

