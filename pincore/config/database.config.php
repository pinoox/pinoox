<?php

use Pinoox\Component\Database\DatabaseManager;

/*
| database config (connections + migrations).
| Redis connections: see redis.config.php (same env keys as Laravel).
|
| Switch driver: DB_CONNECTION=mysql|mariadb|pgsql|sqlsrv|sqlite
| Shared credentials: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, …
*/

$pdoMysqlSslOptions = extension_loaded('pdo_mysql')
    ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ])
    : [];

return [
    /*
    |--------------------------------------------------------------------------
    | Default connection
    |--------------------------------------------------------------------------
    */
    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => env('DB_PREFIX', ''),
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => env('DB_BUSY_TIMEOUT'),
            'journal_mode' => env('DB_JOURNAL_MODE'),
            'synchronous' => env('DB_SYNCHRONOUS'),
            'transaction_mode' => env('DB_TRANSACTION_MODE', 'DEFERRED'),
        ],

        'mysql' => [
            'driver' => env('DB_DRIVER', 'mysql'),
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'pinoox'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'root'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_bin'),
            'prefix' => env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            'prefix_indexes' => env('DB_PREFIX_INDEXES', true),
            'strict' => env('DB_STRICT', true),
            'engine' => env('DB_ENGINE'),
            'timezone' => env('DB_TIMEZONE', '+03:30'),
            'options' => $pdoMysqlSslOptions,
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'pinoox'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'root'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            'prefix_indexes' => env('DB_PREFIX_INDEXES', true),
            'strict' => env('DB_STRICT', true),
            'engine' => env('DB_ENGINE'),
            'timezone' => env('DB_TIMEZONE', '+03:30'),
            'options' => $pdoMysqlSslOptions,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'pinoox'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            'prefix_indexes' => env('DB_PREFIX_INDEXES', true),
            'search_path' => env('DB_SCHEMA', 'public'),
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'pinoox'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', DatabaseManager::DEFAULT_CORE_TABLE_PREFIX),
            'prefix_indexes' => env('DB_PREFIX_INDEXES', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration repository
    |--------------------------------------------------------------------------
    |
    | Pinoox core migrations use the `history` table (see Table::MIGRATION).
    |
    */
    'migrations' => [
        'table' => env('DB_MIGRATIONS_TABLE', 'history'),
        'update_date_on_publish' => env('DB_MIGRATIONS_UPDATE_DATE_ON_PUBLISH', true),
    ],
];
