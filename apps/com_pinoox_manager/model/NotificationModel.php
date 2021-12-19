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

namespace pinoox\app\com_pinoox_manager\model;

use pinoox\component\Date;

class NotificationModel extends ManagerDatabase
{

    const pending = 'pending';
    const send = 'send';
    const seen = 'seen';
    const hide = 'hide';

    public static function fetch_all($limit = null, $status = null)
    {
        if (is_array($status))
            self::$db->where('status', $status, 'IN');
        else if (!empty($status))
            self::$db->where('status', $status);

        self::$db->orderBy('insert_date', 'DESC');
        return self::$db->get(self::notification, $limit);
    }

    public static function fetch_action($action_key, $app = null)
    {
        if (!empty($app))
            self::$db->where('app', $app);

        self::$db->where('action_key', $action_key);
        self::$db->orderBy('insert_date', 'DESC');
        return self::$db->getOne(self::notification);
    }

    public static function fetch_actions($action_key, $app = null, $limit = null)
    {
        if (!empty($app))
            self::$db->where('app', $app);

        self::$db->where('action_key', $action_key);
        self::$db->orderBy('insert_date', 'DESC');
        return self::$db->get(self::notification, $limit);
    }

    public static function update_status($ntf_id, $status)
    {
        self::$db->where('ntf_id', $ntf_id);
        return self::$db->update(self::notification, [
            'status' => $status
        ]);
    }

    public static function update_pending_by_push_date()
    {
        $now = Date::g('Y-m-d H:i:s');
        self::$db->where('push_date', $now, '<=');
        self::$db->where('status', self::pending);
        return self::$db->update(self::notification, [
            'status' => self::send,
        ]);
    }

    public static function insert($data)
    {
        $insert_date = Date::g('Y-m-d H:i:s');
        $data['push_date'] = !empty($data['push_date']) ? Date::g('Y-m-d H:i:s', $data['push_date']) : $insert_date;
        return self::$db->insert(self::notification, [
            'title' => $data['title'],
            'message' => $data['message'],
            'insert_date' => $insert_date,
            'push_date' => $data['push_date'],
            'app' => $data['app'],
            'action_key' => isset($data['action_key']) ? $data['action_key'] : null,
            'action_data' => isset($data['action_data']) ? $data['action_data'] : null,
            'status' => $data['status'],
        ]);
    }
}