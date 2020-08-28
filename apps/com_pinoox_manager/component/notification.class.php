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

namespace pinoox\app\com_pinoox_manager\component;

use pinoox\app\com_pinoox_manager\model\NotificationModel;
use pinoox\component\HelperString;
use pinoox\component\MagicTrait;
use pinoox\component\Router;

class Notification
{
    use MagicTrait;
    private static $app;
    private static $action;

    public static function __init()
    {
        self::action();
        self::refresh();
    }

    public static function action($action_key = null, $action_data = null)
    {
        self::$action = ['key' => $action_key, 'data' => $action_data];
    }

    public static function refresh()
    {
        NotificationModel::update_pending_by_push_date();
    }

    public static function push($title, $message, $time = 0, $isCheckAction = false)
    {
        $action_key = @self::$action['key'];
        $action_data = @self::$action['data'];
        self::action();

        if ($isCheckAction) {
            $action = self::getAction($action_key);
            if ($action)
                return $action['ntf_id'];
        }

        return NotificationModel::insert([
            'title' => $title,
            'message' => $message,
            'action_key' => $action_key,
            'action_data' => (is_array($action_data)) ? HelperString::encodeJson($action_data) : $action_data,
            'status' => NotificationModel::pending,
            'app' => self::getApp(),
            'push_date' => time() + $time,
        ]);
    }

    public static function getAction($action_key)
    {
        return NotificationModel::fetch_action($action_key, self::getApp());
    }

    private static function getApp()
    {
        return (empty(self::$app)) ? Router::getApp() : self::$app;
    }

    public static function send($ntf_id)
    {
        return NotificationModel::update_status($ntf_id, NotificationModel::send);
    }

    public static function seen($ntf_id)
    {
        return NotificationModel::update_status($ntf_id, NotificationModel::seen);
    }

    public static function hide($ntf_id)
    {
        return NotificationModel::update_status($ntf_id, NotificationModel::hide);
    }

    public static function getAll($limit = null)
    {
        return NotificationModel::fetch_all($limit, [NotificationModel::send, NotificationModel::seen]);
    }

    public static function getActions($action_key, $limit = null)
    {
        return NotificationModel::fetch_actions($action_key, self::getApp(), $limit);
    }

    public static function app($packageName)
    {
        self::$app = $packageName;
    }
}