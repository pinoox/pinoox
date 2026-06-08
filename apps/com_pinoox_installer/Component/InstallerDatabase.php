<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Database\DatabaseConfig;
use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Http\FormRequest;
use Pinoox\Component\Http\Request;
use Pinoox\Portal\Database\DB;

final class InstallerDatabase
{
    /** @return list<string> */
    public const INSTALLABLE_CONNECTIONS = ['mysql', 'mariadb', 'pgsql', 'sqlsrv'];

    /** @var array<string, string> */
    public const CONNECTION_LABELS = [
        'mysql' => 'MySQL',
        'mariadb' => 'MariaDB',
        'pgsql' => 'PostgreSQL',
        'sqlsrv' => 'SQL Server',
    ];

    /**
     * @return array{available: bool, extension: ?string}
     */
    public static function extensionStatus(string $connection): array
    {
        return match ($connection) {
            'pgsql' => [
                'available' => extension_loaded('pdo_pgsql'),
                'extension' => extension_loaded('pdo_pgsql') ? 'PDO PostgreSQL' : null,
            ],
            'sqlsrv' => [
                'available' => extension_loaded('pdo_sqlsrv'),
                'extension' => extension_loaded('pdo_sqlsrv') ? 'PDO SQL Server' : null,
            ],
            'mysql', 'mariadb' => [
                'available' => extension_loaded('pdo_mysql') || extension_loaded('mysqli'),
                'extension' => extension_loaded('pdo_mysql')
                    ? 'PDO MySQL'
                    : (extension_loaded('mysqli') ? 'MySQLi' : null),
            ],
            default => ['available' => false, 'extension' => null],
        };
    }

    /** @return list<string> */
    public static function availableConnections(): array
    {
        $available = [];

        foreach (self::INSTALLABLE_CONNECTIONS as $connection) {
            if (self::extensionStatus($connection)['available']) {
                $available[] = $connection;
            }
        }

        return $available;
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function connectionName(array $input): string
    {
        $name = strtolower(trim((string) ($input['connection'] ?? DatabaseConfig::DEFAULT_CONNECTION)));

        return in_array($name, self::INSTALLABLE_CONNECTIONS, true)
            ? $name
            : DatabaseConfig::DEFAULT_CONNECTION;
    }

    public static function defaultPort(string $connection): string
    {
        return match ($connection) {
            'pgsql' => '5432',
            'sqlsrv' => '1433',
            default => '3306',
        };
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $connection = self::connectionName($input);
        $port = (string) ($input['port'] ?? '');

        if ($port === '') {
            $port = self::defaultPort($connection);
        }

        $shared = [
            'host' => $input['host'] ?? '127.0.0.1',
            'database' => $input['database'] ?? null,
            'username' => $input['username'] ?? 'root',
            'password' => $input['password'] ?? '',
            'prefix' => $input['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
            'port' => $port,
        ];

        $config = match ($connection) {
            'pgsql' => array_merge($shared, [
                'driver' => 'pgsql',
                'charset' => 'utf8',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]),
            'sqlsrv' => array_merge($shared, [
                'driver' => 'sqlsrv',
                'charset' => 'utf8',
                'prefix_indexes' => true,
            ]),
            'mariadb' => array_merge($shared, [
                'driver' => 'mariadb',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'strict' => true,
                'engine' => 'InnoDB',
                'timezone' => (string) ($input['timezone'] ?? '+03:30'),
            ]),
            default => array_merge($shared, [
                'driver' => 'mysql',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_bin',
                'strict' => true,
                'engine' => 'InnoDB',
                'timezone' => (string) ($input['timezone'] ?? '+03:30'),
            ]),
        };

        return DatabaseConfig::normalizeConnectionDriver($config);
    }

    /**
     * @return array<string, mixed>
     */
    public static function readFromRequest(Request|FormRequest $request): array
    {
        $request = self::resolveRequest($request);
        $keys = ['connection', 'host', 'database', 'username', 'password', 'prefix', 'port', 'timezone'];
        $nested = $request->payload('db');

        if (is_array($nested) && $nested !== []) {
            return array_intersect_key($nested, array_flip($keys));
        }

        return $request->payloadMany('connection,host,database,username,password,prefix,port,timezone', '', false);
    }

    /**
     * Merge validated setup payload with the raw request (keeps password/prefix reliably).
     *
     * @param array<string, mixed> $validatedDb
     * @return array<string, mixed>
     */
    public static function readForSetup(Request|FormRequest $request, array $validatedDb = []): array
    {
        $input = array_replace($validatedDb, self::readFromRequest($request));
        $connection = self::connectionName($input);

        return array_merge(
            ['connection' => $connection],
            self::normalize($input),
        );
    }

    private static function resolveRequest(Request|FormRequest $request): Request
    {
        return $request instanceof FormRequest ? $request->global : $request;
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function testConnection(array $input): bool
    {
        $config = self::normalize($input);

        if (empty($config['database'])) {
            return false;
        }

        if (!InstallerDatabase::extensionStatus(InstallerDatabase::connectionName($input))['available']) {
            return false;
        }

        try {
            DB::addConnection($config);
            DB::bootEloquent();
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
