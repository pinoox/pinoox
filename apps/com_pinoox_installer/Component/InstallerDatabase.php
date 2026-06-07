<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Http\FormRequest;
use Pinoox\Component\Http\Request;
use Pinoox\Portal\Database\DB;

final class InstallerDatabase
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        return [
            'host' => $input['host'] ?? '127.0.0.1',
            'database' => $input['database'] ?? null,
            'username' => $input['username'] ?? 'root',
            'password' => $input['password'] ?? '',
            'prefix' => $input['prefix'] ?? DatabaseManager::DEFAULT_CORE_TABLE_PREFIX,
            'driver' => 'mysql',
            'port' => (string) ($input['port'] ?? '3306'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'strict' => true,
            'engine' => 'InnoDB',
            'timezone' => (string) ($input['timezone'] ?? '+03:30'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function readFromRequest(Request|FormRequest $request): array
    {
        $request = self::resolveRequest($request);
        $keys = ['host', 'database', 'username', 'password', 'prefix', 'port'];
        $nested = $request->payload('db');

        if (is_array($nested) && $nested !== []) {
            return array_intersect_key($nested, array_flip($keys));
        }

        return $request->payloadMany('host,database,username,password,prefix,port', '', false);
    }

    /**
     * Merge validated setup payload with the raw request (keeps password/prefix reliably).
     *
     * @param array<string, mixed> $validatedDb
     * @return array<string, mixed>
     */
    public static function readForSetup(Request|FormRequest $request, array $validatedDb = []): array
    {
        return self::normalize(array_replace($validatedDb, self::readFromRequest($request)));
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

