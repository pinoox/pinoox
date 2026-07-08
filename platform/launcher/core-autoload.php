<?php

/**
 * Prefer the resolved core path over Composer's vendor classmap for Pinoox\ classes.
 */

function pinoox_register_core_autoload(\Composer\Autoload\ClassLoader $loader, string $basePath, string $corePath): void
{
    $corePath = pinoox_normalize_path(rtrim($corePath, '/')) . '/';
    $vendorCore = pinoox_normalize_path(pinoox_default_core_vendor_path($basePath)) . '/';

    if ($corePath === $vendorCore) {
        return;
    }

    $overrideMap = [];
    foreach ($loader->getClassMap() as $class => $path) {
        if (!str_starts_with($class, 'Pinoox\\')) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, 7)) . '.php';
        $localFile = $corePath . $relative;

        if (is_file($localFile)) {
            $overrideMap[$class] = $localFile;
        }
    }

    if ($overrideMap !== []) {
        $loader->addClassMap($overrideMap);
    }

    $loader->addPsr4('Pinoox\\', $corePath, true);

    spl_autoload_register(static function (string $class) use ($corePath): void {
        if (!str_starts_with($class, 'Pinoox\\')) {
            return;
        }

        $file = $corePath . str_replace('\\', '/', substr($class, 7)) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }, true, true);
}
