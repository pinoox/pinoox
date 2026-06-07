<?php

namespace Pinoox\Component\Router\Action;

use Pinoox\Component\Router\Route;
use Pinoox\Component\Router\RouteCapsule;
use Pinoox\Component\Router\Router;

class ActionValidator
{
    /**
     * @return list<string>
     */
    public function validate(Router $router, string $package, bool $reportUnused = true): array
    {
        $errors = [];
        $registeredKeys = array_keys($router->actions);
        $seen = [];

        foreach ($router->all() as $routeName => $routeCapsule) {
            if (!$routeCapsule instanceof RouteCapsule) {
                continue;
            }

            $controller = $routeCapsule->getDefault('_controller');
            if (!ActionReference::isReference($controller)) {
                continue;
            }

            $pinooxRoute = $routeCapsule->getDefault('_router');
            $collectionPrefix = $pinooxRoute instanceof Route ? $pinooxRoute->getCollection()->name : '';

            $resolved = ActionReference::resolveKey((string) $controller, $collectionPrefix, $registeredKeys);
            if ($resolved === null) {
                $errors[] = sprintf(
                    'Route "%s" references missing action "%s".',
                    $routeName,
                    $controller,
                );
                continue;
            }

            $seen[$resolved] = true;
        }

        foreach ($registeredKeys as $key) {
            if (isset($seen[$key])) {
                continue;
            }

            if ($reportUnused) {
                $errors[] = sprintf('Action "%s" is registered but not referenced by any route.', $key);
            }
        }

        return array_values(array_unique($errors));
    }

    public function assertValid(Router $router, string $package, bool $reportUnused = false): void
    {
        $errors = $this->validate($router, $package, $reportUnused);
        $critical = array_values(array_filter(
            $errors,
            static fn (string $error) => !str_contains($error, 'not referenced'),
        ));

        if ($critical !== []) {
            throw new ActionValidationException($critical);
        }
    }
}

