<?php

namespace App\com_pinoox_manager\Component;

use Pinoox\Portal\FileSystem;

/**
 * File-backed install progress for polling during long-running installs.
 */
final class InstallSession
{
    private const DIR = 'manager/install-sessions';

    public static function create(string $filename): string
    {
        ManagerStorage::ensureDir(self::DIR);

        $id = bin2hex(random_bytes(16));
        self::write($id, [
            'id' => $id,
            'filename' => basename($filename),
            'status' => 'running',
            'progress' => 0,
            'steps' => [],
            'result' => null,
            'error' => null,
            'updated_at' => time(),
        ]);

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $id): ?array
    {
        $id = self::sanitizeId($id);
        $path = self::path($id);

        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $patch
     */
    public static function update(string $id, array $patch): void
    {
        $current = self::get($id) ?? ['id' => $id, 'steps' => []];
        $current = array_replace($current, $patch);
        $current['updated_at'] = time();
        self::write($id, $current);
    }

    public static function addStep(string $id, string $step, string $status, string $message): void
    {
        $session = self::get($id);

        if ($session === null) {
            return;
        }

        $steps = is_array($session['steps'] ?? null) ? $session['steps'] : [];
        $steps[] = [
            'step' => $step,
            'status' => $status,
            'message' => $message,
        ];

        $progress = self::progressFromSteps($steps, (string) ($session['status'] ?? 'running'));

        self::update($id, [
            'steps' => $steps,
            'progress' => $progress,
        ]);
    }

    /**
     * @param array<string, mixed> $result
     */
    public static function complete(string $id, array $result): void
    {
        self::update($id, [
            'status' => !empty($result['success']) ? 'done' : 'failed',
            'progress' => 100,
            'result' => $result,
            'error' => $result['message'] ?? null,
        ]);
    }

    public static function remove(string $id): void
    {
        $path = self::path(self::sanitizeId($id));

        if (is_file($path)) {
            FileSystem::remove($path);
        }
    }

    /**
     * @param list<array{step: string, status: string, message: string}> $steps
     */
    private static function progressFromSteps(array $steps, string $status): int
    {
        if ($status === 'done') {
            return 100;
        }

        if ($steps === []) {
            return 0;
        }

        $weights = [
            'validate' => 5,
            'signature' => 5,
            'minpin' => 5,
            'depends' => 5,
            'detect' => 5,
            'target' => 5,
            'identity' => 5,
            'pinker_snapshot' => 5,
            'extract' => 20,
            'database' => 10,
            'theme_meta' => 5,
            'pinker' => 10,
            'registry' => 5,
            'migrate' => 15,
            'patch' => 5,
            'cache' => 5,
            'complete' => 5,
        ];

        $total = array_sum($weights);
        $earned = 0;

        foreach ($steps as $entry) {
            $key = (string) ($entry['step'] ?? '');
            $earned += $weights[$key] ?? 3;
        }

        return min(99, (int) round(($earned / max(1, $total)) * 100));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function write(string $id, array $data): void
    {
        file_put_contents(
            self::path($id),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX,
        );
    }

    private static function path(string $id): string
    {
        return ManagerStorage::disk()->path(self::DIR . '/' . $id . '.json');
    }

    private static function sanitizeId(string $id): string
    {
        return preg_replace('/[^a-f0-9]/', '', strtolower($id)) ?: '';
    }
}
