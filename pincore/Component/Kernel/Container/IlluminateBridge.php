<?php

namespace Pinoox\Component\Kernel\Container;

use Illuminate\Container\Container as IlluminateContainer;
use Pinoox\Component\Kernel\Container;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

class IlluminateBridge
{
    private static ?IlluminateContainer $illuminate = null;

    public static function instance(): IlluminateContainer
    {
        if (self::$illuminate === null) {
            self::$illuminate = Container::Illuminate();
            self::$illuminate->instance(ContainerInterface::class, Container::platform());
            self::$illuminate->instance(IlluminateContainer::class, self::$illuminate);
        }

        return self::$illuminate;
    }

    /**
     * @param array<string, string> $bindings
     * @param array<string, bool> $singletons
     */
    public static function applyBindings(array $bindings, array $singletons = []): void
    {
        $container = self::instance();

        foreach ($bindings as $abstract => $concrete) {
            if (!is_string($abstract) || !is_string($concrete)) {
                continue;
            }

            self::bind($abstract, $concrete);
        }

        foreach ($singletons as $abstract => $enabled) {
            if (!$enabled || !is_string($abstract)) {
                continue;
            }

            $container->singleton($abstract);
        }
    }

    public static function bind(string $abstract, string $concrete): void
    {
        self::instance()->bind($abstract, $concrete);
    }

    public static function make(string $class): object
    {
        return self::instance()->make($class);
    }

    /**
     * @param class-string $class
     */
    public static function canBuild(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return true;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if (!self::canResolveParameter($parameter)) {
                return false;
            }
        }

        return true;
    }

    private static function canResolveParameter(ReflectionParameter $parameter): bool
    {
        if ($parameter->isDefaultValueAvailable()) {
            return true;
        }

        $type = $parameter->getType();
        if ($type === null || $type->isBuiltin()) {
            return $parameter->isOptional();
        }

        return class_exists($type->getName()) || interface_exists($type->getName());
    }
}

