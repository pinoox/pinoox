<?php

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Database\DatabaseManager;
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
    public static function readFromRequest(Request $request): array
    {
        $keys = 'host,database,username,password,prefix';
        $data = $request->request($keys, '', '!empty');

        if (empty($data['host']) && empty($data['database'])) {
            $data = $request->json($keys, '', '!empty');
        }

        return is_array($data) ? $data : [];
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
