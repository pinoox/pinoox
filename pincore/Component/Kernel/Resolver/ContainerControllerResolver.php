<?php

namespace Pinoox\Component\Kernel\Resolver;

use Pinoox\Component\Kernel\Container\ControllerAutowirer;
use Pinoox\Component\Kernel\Container\ServiceContainerBootstrap;
use Pinoox\Component\Kernel\Controller\Controller;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver as ContainerControllerResolverSymfony;

class ContainerControllerResolver extends ContainerControllerResolverSymfony
{
    protected function instantiateController(string $class): object
    {
        if ($this->container->has($class)) {
            $controller = $this->container->get($class);
            ControllerAutowirer::injectContainer($controller, $this->container);

            return $controller;
        }

        if (ServiceContainerBootstrap::autowireControllers()) {
            return ControllerAutowirer::instantiate($class, $this->container);
        }

        $controller = parent::instantiateController($class);
        ControllerAutowirer::injectContainer($controller, $this->container);

        return $controller;
    }
}

