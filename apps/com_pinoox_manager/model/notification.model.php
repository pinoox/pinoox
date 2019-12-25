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

class NotificationModel extends ManagerDatabase
{

    public static function fetch_all($limit = null, $is_seen = null)
    {
        if (!is_null($is_seen))
            self::$db->where('is_seen', $is_seen);
        self::$db->orderBy('insert_date', 'DESC');
        return self::$db->get(self::notification, $limit);
    }

    public static function update_seen($ntf_id, $is_seen)
    {
        self::$db->where('ntf_id', $ntf_id);
        return self::$db->update(self::notification, [
            'is_seen' => $is_seen
        ]);
    }
}