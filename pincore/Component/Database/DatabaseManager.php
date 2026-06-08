<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       pinoox.com
 * @copyright  pinoox
 */

namespace Pinoox\Component\Database;

use Illuminate\Contracts\Database\Query\Expression as ObjectPortal4;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\Platform;
use Pinoox\Support\SystemApp;

/**
 * @mixin Connection
 */
class DatabaseManager extends Capsule
{

    public const DEFAULT_CONNECTION = 'default';

    public const CORE_CONNECTION = Platform::PACKAGE;

    public const DEFAULT_CORE_TABLE_PREFIX = 'pinx_';

    private array $packageConnections = [];

    public function registerCoreConnection(array $config): void
    {
        $this->addConnection($config, self::DEFAULT_CONNECTION);
        $this->addConnection($config, self::CORE_CONNECTION);
    }

    public function currentConnection($connection = null): Connection
    {
        return $this->getConnection($connection ?? $this->currentConnectionName());
    }

    public function core(): Connection
    {
        return $this->getConnection(self::CORE_CONNECTION);
    }

    public function app(?string $package = null, string $name = 'default'): Connection
    {
        return $this->getConnection($this->connectionNameForPackage($package, $name));
    }

    public function package(?string $package = null, string $name = 'default'): Connection
    {
        return $this->app($package, $name);
    }

    public function currentSchema($connection = null)
    {
        return $this->currentConnection($connection)->getSchemaBuilder();
    }

    public function currentTable($table, $as = null, $connection = null)
    {
        if ($connection === null && is_string($table)) {
            $table = $this->tableName($table, App::package());
        }

        return $this->currentConnection($connection)->table($table, $as);
    }

    public function currentConnectionName(): string
    {
        return $this->connectionNameForPackage(App::package());
    }

    public function connectionNameForModel(string $class): string
    {
        if ($this->isSystemModel($class)) {
            return self::CORE_CONNECTION;
        }

        if (preg_match('/^App\\\\([^\\\\]+)\\\\/', $class, $matches)) {
            return $this->connectionNameForPackage($matches[1]);
        }

        return $this->currentConnectionName();
    }

    public function tableName(string $table, ?string $package = null): string
    {
        $table = trim($table);

        if ($table === '' || $this->isQualifiedTable($table)) {
            return $table;
        }

        [$name, $alias] = $this->splitTableAlias($table);
        $prefix = $this->tablePrefixForPackage($package);

        if ($prefix !== '' && !$this->hasTablePrefix($name, $prefix, $package)) {
            $name = $prefix . $name;
        }

        return $alias ? $name . ' AS ' . $alias : $name;
    }

    public function tableNameForModel(string $table, string $class): string
    {
        return $this->tableName($table, $this->packageNameForModel($class));
    }

    public function physicalTableName(string $table, ?string $package = null): string
    {
        $table = $this->tableName($table, $package);

        if ($table === '' || $this->isQualifiedTable($table)) {
            return $table;
        }

        [$name, $alias] = $this->splitTableAlias($table);
        $connection = $this->connectionNameForPackage($package);
        $prefix = $this->connectionPrefix($connection);

        if ($prefix !== '' && !$this->hasTablePrefix($name, $prefix, $package)) {
            $name = $prefix . $name;
        }

        return $alias ? $name . ' AS ' . $alias : $name;
    }

    public function tablePrefixForPackage(?string $package = null): string
    {
        if (empty($package) || $package === '~') {
            return '';
        }

        if ($package === self::CORE_CONNECTION) {
            return $this->connectionPrefix(self::CORE_CONNECTION) !== '' ? '' : self::DEFAULT_CORE_TABLE_PREFIX;
        }

        if (!AppEngine::exists($package)) {
            return $this->shortPackagePrefix($package);
        }

        $config = AppEngine::config($package);
        $database = $config->get('database');
        $explicitPrefix = $this->explicitTablePrefix($database, $config->get('table'));

        if ($explicitPrefix !== null) {
            return $this->packageConnectionPrefix($package) === $explicitPrefix ? '' : $explicitPrefix;
        }

        return empty($this->normalizePackageConnections($database))
            ? $this->shortPackagePrefix($package)
            : '';
    }

    public function packageNameForModel(string $class): ?string
    {
        if ($this->isSystemModel($class)) {
            return self::CORE_CONNECTION;
        }

        if (preg_match('/^App\\\\([^\\\\]+)\\\\/', $class, $matches)) {
            return $matches[1];
        }

        return App::package();
    }

    public function connectionNameForPackage(?string $package = null, string $name = 'default'): string
    {
        if (empty($package) || $package === '~') {
            return self::DEFAULT_CONNECTION;
        }

        if ($package === self::CORE_CONNECTION) {
            return self::CORE_CONNECTION;
        }

        $this->registerPackageConnections($package);

        return $this->packageConnections[$package][$name]
            ?? $this->packageConnections[$package]['default']
            ?? self::DEFAULT_CONNECTION;
    }

    public function registerPackageConnections(string $package): bool
    {
        if (array_key_exists($package, $this->packageConnections)) {
            return !empty($this->packageConnections[$package]);
        }

        $this->packageConnections[$package] = [];

        if (!AppEngine::exists($package)) {
            return false;
        }

        $config = AppEngine::config($package);
        $database = $config->get('database');
        $table = $config->get('table');
        $explicitPrefix = $this->explicitTablePrefix($database, $table);

        if ($this->isPrefixOnlyConfig($database, $table)) {
            $connections = [];
            $cloned = $this->cloneCoreConnectionWithPrefix((string) $explicitPrefix);

            if ($cloned !== null) {
                $connections = ['default' => $cloned];
            }
        } else {
            $connections = $this->normalizePackageConnections($database, $table);
        }

        if (empty($connections)) {
            return false;
        }

        foreach ($connections as $name => $config) {
            $connectionName = $this->buildPackageConnectionName($package, $name);
            $this->addConnection($config, $connectionName);
            $this->packageConnections[$package][$name] = $connectionName;
        }

        $default = is_array($database) ? $this->defaultPackageConnectionName($database) : 'default';
        if ($default !== 'default' && isset($this->packageConnections[$package][$default])) {
            $this->packageConnections[$package]['default'] = $this->packageConnections[$package][$default];
        }

        return true;
    }

    private function normalizePackageConnections(mixed $database, ?array $table = null): array
    {
        if (empty($database) || !is_array($database)) {
            $database = [];
        }

        return AppDatabaseResolver::resolve(
            $database === [] ? null : $database,
            is_array($table) ? $table : null,
        );
    }

    private function defaultPackageConnectionName(array $database): string
    {
        return AppDatabaseResolver::defaultConnectionName($database);
    }

    private function explicitTablePrefix(mixed $database, mixed $table): ?string
    {
        return AppDatabaseResolver::tablePrefix(
            is_array($database) ? $database : null,
            is_array($table) ? $table : null,
        ) ?? AppDatabaseResolver::connectionPrefix(is_array($database) ? $database : null);
    }

    private function connectionPrefix(string $connection): string
    {
        try {
            return (string)$this->getConnection($connection)->getTablePrefix();
        } catch (\Throwable) {
            return '';
        }
    }

    private function packageConnectionPrefix(string $package): string
    {
        if (!array_key_exists($package, $this->packageConnections)) {
            $this->registerPackageConnections($package);
        }

        $connection = $this->packageConnections[$package]['default'] ?? null;

        return is_string($connection) ? $this->connectionPrefix($connection) : '';
    }

    private function cloneCoreConnectionWithPrefix(string $prefix): ?array
    {
        try {
            $config = $this->getConnection(self::CORE_CONNECTION)->getConfig();
            $config['prefix'] = $prefix;

            return $config;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed>|null $database
     * @param array<string, mixed>|null $table
     */
    private function isPrefixOnlyConfig(?array $database, ?array $table): bool
    {
        if ($this->explicitTablePrefix($database, $table) === null) {
            return false;
        }

        if (!is_array($database) || $database === []) {
            return true;
        }

        $keys = array_keys(array_filter(
            $database,
            static fn ($value) => $value !== null && $value !== '',
        ));

        return array_diff($keys, ['prefix', 'table_prefix', 'use', 'connection']) === [];
    }

    private function buildPackageConnectionName(string $package, string $name): string
    {
        $package = preg_replace('/[^A-Za-z0-9_]+/', '_', $package);
        $name = preg_replace('/[^A-Za-z0-9_]+/', '_', $name);

        return 'app_' . $package . '_' . $name;
    }

    private function shortPackagePrefix(string $package): string
    {
        $parts = array_values(array_filter(explode('_', $package)));
        $name = end($parts) ?: $package;
        $name = preg_replace('/[^A-Za-z0-9_]+/', '_', strtolower($name));

        return trim($name, '_') . '_';
    }

    private function isSystemModel(string $class): bool
    {
        return str_starts_with($class, 'Pinoox\\Model\\')
            || str_starts_with($class, 'Pinoox\\System\\Model\\');
    }

    private function isQualifiedTable(string $table): bool
    {
        return str_contains($table, '.')
            || str_contains($table, '(')
            || (str_contains($table, ' ') && !preg_match('/\s+as\s+/i', $table));
    }

    private function splitTableAlias(string $table): array
    {
        if (preg_match('/^(.+?)\s+as\s+(.+)$/i', $table, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [$table, null];
    }

    private function hasTablePrefix(string $table, string $prefix, ?string $package): bool
    {
        return str_starts_with($table, $prefix)
            || str_starts_with($table, self::DEFAULT_CORE_TABLE_PREFIX)
            || (!empty($package) && str_starts_with($table, $package . '_'));
    }

    public function setPrefix(string $prefix): void
    {
        $this->currentConnection()->setTablePrefix($prefix);
    }

    public function orderColumn(array|string $field): string|ObjectPortal4
    {
        if (is_array($field)) {
            return $this->raw("CONCAT(" . implode(', \' \', ', $field) . ")");
        }

        return $field;
    }

    public function orderDirection(string $type): string
    {
        return strtolower($type) === 'asc' ? 'asc' : 'desc';
    }

    public function __call($method, $parameters)
    {
        return $this->currentConnection()->$method(...$parameters);
    }
}

