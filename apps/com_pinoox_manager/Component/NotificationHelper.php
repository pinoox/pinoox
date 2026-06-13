<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_manager\Component;

use Pinoox\Portal\Config;

class NotificationHelper
{
    const pending = 'pending';
    const send = 'send';
    const seen = 'seen';
    const hide = 'hide';

    private static function all(): array
    {
        return Config::name('notifications')->get('items') ?? [];
    }

    private static function save(array $items): void
    {
        Config::name('notifications')->set('items', $items)->save();
    }

    public static function refresh(): void
    {
        $items = self::all();
        $now = time();
        $changed = false;

        foreach ($items as &$item) {
            if ($item['status'] === self::pending && $item['push_date'] <= $now) {
                $item['status'] = self::send;
                $changed = true;
            }
        }

        if ($changed)
            self::save($items);
    }

    public static function push($title, $message, $time = 0, $isCheckAction = false, $action_key = null, $action_data = null): ?int
    {
        if ($isCheckAction && $action_key) {
            $existing = self::getAction($action_key);
            if ($existing)
                return $existing['ntf_id'];
        }

        $items = self::all();
        $ntf_id = empty($items) ? 1 : max(array_column($items, 'ntf_id')) + 1;

        $items[] = [
            'ntf_id' => $ntf_id,
            'title' => $title,
            'message' => $message,
            'insert_date' => date('Y-m-d H:i:s'),
            'push_date' => time() + $time,
            'action_key' => $action_key,
            'action_data' => is_array($action_data) ? json_encode($action_data) : $action_data,
            'status' => self::pending,
            'app' => app()->package(),
        ];

        self::save($items);
        self::refresh();

        return $ntf_id;
    }

    public static function getAction($action_key): ?array
    {
        foreach (self::all() as $item) {
            if ($item['action_key'] === $action_key)
                return $item;
        }
        return null;
    }

    public static function updateStatus($ntf_id, $status): bool
    {
        $items = self::all();
        $found = false;

        foreach ($items as &$item) {
            if ($item['ntf_id'] == $ntf_id) {
                $item['status'] = $status;
                $found = true;
                break;
            }
        }

        if ($found)
            self::save($items);

        return $found;
    }

    public static function getAll($limit = null): array
    {
        self::refresh();
        $items = array_filter(self::all(), fn($item) => in_array($item['status'], [self::send, self::seen]));
        usort($items, fn($a, $b) => strcmp($b['insert_date'], $a['insert_date']));

        if ($limit)
            $items = array_slice($items, 0, $limit);

        return array_values($items);
    }
}

