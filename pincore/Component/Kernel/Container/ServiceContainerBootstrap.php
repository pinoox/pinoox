<?php

namespace Pinoox\Component\Kernel\Container;

use Pinoox\Component\Kernel\ContainerBuilder;
use Pinoox\Component\Kernel\Container as KernelContainer;
use Pinoox\Portal\App\AppEngine;

class ServiceContainerBootstrap
{
    /** @var array<string, true> */

    private static array $booted = [];

    public static function boot(?string $package = null): ContainerBuilder
    {
        $package = $package ?? (string) (\Pinoox\Portal\App\App::package() ?? '');
        $builder = $package === '' || $package === '~'
            ? KernelContainer::platform()
            : KernelContainer::app($package);

        $key = self::key($package);
        if (isset(self::$booted[$key])) {
            return $builder;
        }

        self::registerServiceContainer($builder);

        if ($package !== '' && $package !== '~' && self::containerEnabled($package)) {
            $cached = AppServiceContainer::export($package);
            if (is_array($cached)) {
                ControllerAutowirer::rebuildFromCache($package, $cached, $builder);
            } else {
                AppServiceContainer::register($builder, $package);
            }
        }

        self::$booted[$key] = true;

        return $builder;
    }

    public static function serviceContainer(?string $package = null): ContainerBuilder
    {
        return self::boot($package);
    }

    public static function containerEnabled(?string $package = null): bool
    {
        $package = $package ?? (string) (\Pinoox\Portal\App\App::package() ?? '');
        if ($package === '') {
            return false;
        }

        try {
            $config = AppEngine::config($package)->get('container');
        } catch (\Throwable) {
            return false;
        }

        return is_array($config) && (bool) ($config['enabled'] ?? false);
    }

    public static function autowireControllers(?string $package = null): bool
    {
        if (!self::containerEnabled($package)) {
            return false;
        }

        $package = $package ?? (string) (\Pinoox\Portal\App\App::package() ?? '');

        try {
            $config = AppEngine::config($package)->get('container');
        } catch (\Throwable) {
            return true;
        }

        if (!is_array($config)) {
            return true;
        }

        return (bool) ($config['autowire_controllers'] ?? true);
    }

    private static function registerServiceContainer(ContainerBuilder $builder): void
    {
        if (!$builder->has('kernel.service_container')) {
            $builder->set('kernel.service_container', $builder);
        }

        if (!$builder->hasAlias('service_container')) {
            $builder->setAlias('service_container', 'kernel.service_container');
        }
    }

    private static function key(string $package): string
    {
        return $package === '' ? '~' : $package;
    }
}

