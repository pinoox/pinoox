<?php

namespace Pinoox\Component\Package;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Package\Engine\AppEngine;

/**
 * Declared app dependencies from app.php / pinx manifest.
 *
 * Supported forms in app.php:
 *   'depends' => ['com_base_shop', 'com_pinoox_manager']
 *   'depends' => ['com_base_shop' => '>=2']
 *   'depends' => ['com_opt' => ['optional' => true, 'min_code' => 1]]
 */
final class AppDependency
{
    /**
     * @return list<array{package: string, optional: bool, min_code: ?int}>
     */
    public static function normalize(mixed $depends): array
    {
        if (!is_array($depends) || $depends === []) {
            return [];
        }

        $rules = [];

        if (array_is_list($depends)) {
            foreach ($depends as $package) {
                if (!is_string($package) || trim($package) === '') {
                    continue;
                }

                $rules[] = self::rule(trim($package), null, false);
            }

            return $rules;
        }

        foreach ($depends as $package => $constraint) {
            if (is_int($package) && is_string($constraint) && trim($constraint) !== '') {
                $rules[] = self::rule(trim($constraint), null, false);
                continue;
            }

            if (!is_string($package) || trim($package) === '') {
                continue;
            }

            $package = trim($package);
            $optional = false;
            $minCode = null;

            if (is_array($constraint)) {
                $optional = (bool) ($constraint['optional'] ?? false);
                if (isset($constraint['min_code'])) {
                    $minCode = max(0, (int) $constraint['min_code']);
                } elseif (isset($constraint['version_code'])) {
                    $minCode = max(0, (int) $constraint['version_code']);
                }
            } elseif (is_int($constraint)) {
                $minCode = max(0, $constraint);
            } elseif (is_string($constraint)) {
                $minCode = self::parseConstraint(trim($constraint));
            } elseif ($constraint === true || $constraint === null) {
                $minCode = null;
            }

            $rules[] = self::rule($package, $minCode, $optional);
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $appConfig
     * @return list<array{package: string, optional: bool, min_code: ?int}>
     */
    public static function fromAppConfig(array $appConfig): array
    {
        return self::normalize($appConfig['depends'] ?? []);
    }

    /**
     * @param list<array{package: string, optional: bool, min_code: ?int}> $rules
     */
    public static function assertSatisfied(array $rules, AppEngine $engine): void
    {
        $errors = self::collectErrors($rules, $engine);

        if ($errors !== []) {
            throw new Exception(implode(' ', $errors));
        }
    }

    /**
     * @param list<array{package: string, optional: bool, min_code: ?int}> $rules
     */
    public static function isSatisfied(array $rules, AppEngine $engine): bool
    {
        return self::collectErrors($rules, $engine) === [];
    }

    /**
     * @param list<array{package: string, optional: bool, min_code: ?int}> $rules
     * @return list<string>
     */
    public static function collectErrors(array $rules, AppEngine $engine): array
    {
        $errors = [];

        foreach ($rules as $rule) {
            if (!empty($rule['optional'])) {
                continue;
            }

            $message = self::checkRule($rule, $engine);
            if ($message !== null) {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    /**
     * @param list<array{package: string, optional: bool, min_code: ?int}> $rules
     * @return list<string>
     */
    public static function packages(array $rules): array
    {
        return array_values(array_unique(array_map(static fn (array $rule): string => $rule['package'], $rules)));
    }

    /**
     * @param list<array{package: string, optional: bool, min_code: ?int}> $rules
     * @return list<array{package: string, optional: bool, min_code: ?int, installed: bool, version_code: ?int}>
     */
    public static function inspect(array $rules, AppEngine $engine): array
    {
        $rows = [];

        foreach ($rules as $rule) {
            $installedCode = self::installedVersionCode($rule['package'], $engine);

            $rows[] = [
                'package' => $rule['package'],
                'optional' => (bool) $rule['optional'],
                'min_code' => $rule['min_code'],
                'installed' => $installedCode !== null,
                'version_code' => $installedCode,
                'satisfied' => self::checkRule($rule, $engine) === null,
            ];
        }

        return $rows;
    }

    /**
     * @param array{package: string, optional: bool, min_code: ?int} $rule
     */
    private static function checkRule(array $rule, AppEngine $engine): ?string
    {
        $package = $rule['package'];

        if (!$engine->checkName($package)) {
            return sprintf('Invalid dependency package name "%s".', $package);
        }

        if (!$engine->exists($package)) {
            return sprintf('Required app "%s" is not installed.', $package);
        }

        if ($rule['min_code'] === null) {
            return null;
        }

        $installedCode = self::installedVersionCode($package, $engine);

        if ($installedCode === null) {
            return sprintf('Required app "%s" is not installed.', $package);
        }

        if ($installedCode < $rule['min_code']) {
            return sprintf(
                'Required app "%s" version code %d or higher (installed: %d).',
                $package,
                $rule['min_code'],
                $installedCode,
            );
        }

        return null;
    }

    private static function installedVersionCode(string $package, AppEngine $engine): ?int
    {
        if (!$engine->exists($package)) {
            return null;
        }

        $appFile = $engine->path($package, 'app.php');
        if (!is_file($appFile)) {
            return null;
        }

        $data = include $appFile;

        return is_array($data) ? (int) ($data['version-code'] ?? 0) : null;
    }

    private static function parseConstraint(string $constraint): ?int
    {
        if ($constraint === '' || $constraint === '*' || $constraint === 'any') {
            return null;
        }

        if (preg_match('/^>=?\s*(\d+)$/', $constraint, $matches) === 1) {
            return (int) $matches[1];
        }

        if (ctype_digit($constraint)) {
            return (int) $constraint;
        }

        throw new Exception('Unsupported dependency constraint: ' . $constraint);
    }

    /**
     * Order packages so dependencies are provisioned first.
     *
     * @param list<string> $packages
     * @return list<string>
     */
    public static function sortForInstall(array $packages, AppEngine $engine): array
    {
        $packages = array_values(array_unique(array_filter(
            $packages,
            static fn (mixed $package): bool => is_string($package) && $package !== '',
        )));

        if ($packages === []) {
            return [];
        }

        $set = array_fill_keys($packages, true);
        $incoming = array_fill_keys($packages, 0);
        $dependents = array_fill_keys($packages, []);

        foreach ($packages as $package) {
            foreach (self::requiredPackagesInSet($package, $engine, $set) as $dependency) {
                $dependents[$dependency][] = $package;
                $incoming[$package]++;
            }
        }

        $queue = [];
        foreach ($incoming as $package => $count) {
            if ($count === 0) {
                $queue[] = $package;
            }
        }

        sort($queue);

        $ordered = [];
        while ($queue !== []) {
            $package = array_shift($queue);
            $ordered[] = $package;

            foreach ($dependents[$package] as $dependent) {
                $incoming[$dependent]--;

                if ($incoming[$dependent] === 0) {
                    $queue[] = $dependent;
                    sort($queue);
                }
            }
        }

        if (count($ordered) !== count($packages)) {
            $remaining = array_values(array_diff($packages, $ordered));
            throw new Exception('Circular app dependency detected: ' . implode(', ', $remaining));
        }

        return $ordered;
    }

    /**
     * @param array<string, true> $candidateSet
     * @return list<string>
     */
    private static function requiredPackagesInSet(string $package, AppEngine $engine, array $candidateSet): array
    {
        if (!$engine->exists($package)) {
            return [];
        }

        $appFile = $engine->path($package, 'app.php');
        $config = is_file($appFile) ? include $appFile : [];

        if (!is_array($config)) {
            return [];
        }

        $dependencies = [];
        foreach (self::fromAppConfig($config) as $rule) {
            if (!empty($rule['optional'])) {
                continue;
            }

            $dependency = $rule['package'];
            if (isset($candidateSet[$dependency])) {
                $dependencies[] = $dependency;
            }
        }

        return array_values(array_unique($dependencies));
    }

    /**
     * @return array{package: string, optional: bool, min_code: ?int}
     */
    private static function rule(string $package, ?int $minCode, bool $optional): array
    {
        return [
            'package' => $package,
            'optional' => $optional,
            'min_code' => $minCode,
        ];
    }
}

