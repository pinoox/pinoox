<?php

namespace Pinoox\Component\Kernel\Container;

use Pinoox\Component\Kernel\Controller\Controller;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ControllerAutowirer
{
    public static function instantiate(string $class, ContainerInterface $container): object
    {
        if (ServiceContainerBootstrap::autowireControllers() && IlluminateBridge::canBuild($class)) {
            $controller = IlluminateBridge::make($class);
        } else {
            $controller = new $class();
        }

        self::injectContainer($controller, $container);

        return $controller;
    }

    public static function injectContainer(object $controller, ContainerInterface $container): void
    {
        if ($controller instanceof Controller) {
            $controller->setContainer($container);

            return;
        }

        if (method_exists($controller, 'setContainer')) {
            $controller->setContainer($container);
        }
    }

    public static function rebuildFromCache(string $package, array $payload, ContainerBuilder $builder): void
    {
        AppServiceContainer::hydrate($package, $payload);

        if (!ServiceContainerBootstrap::containerEnabled($package)) {
            return;
        }

        foreach ($payload['controllers'] ?? [] as $class) {
            if (!is_string($class) || $builder->has($class)) {
                continue;
            }

            $definition = new \Symfony\Component\DependencyInjection\Definition($class);
            $definition->setPublic(true);
            $builder->setDefinition($class, $definition);
        }
    }
}

