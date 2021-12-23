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
namespace pinoox\model;

use pinoox\component\Date;
use pinoox\component\HelperHeader;
use pinoox\component\Session;

class SessionModel extends PinooxDatabase
{
    public static function fetch_by_id($session_id, $lifetime = 0, $security_token = null)
    {
        self::$db->where('app',Session::getApp());
        $currentDate = Date::g('Y-m-d H:i:s');
        if (!empty($security_token))
            self::$db->where('security_token', $security_token);
        if (!empty($lifetime))
            self::$db->where("TIMESTAMPDIFF(SECOND,IFNULL(update_date,insert_date),?) <= ?", array($currentDate, $lifetime));
        self::$db->where('session_id', $session_id);
        return self::$db->getOne(self::session);
    }

    public static function fetch_all()
    {
        self::$db->where('app',Session::getApp());
        self::$db->get(self::session);
    }

    public static function remove_all_expired($lifetime)
    {
        self::$db->where('app',Session::getApp());
        if (!empty($lifetime))
        {
            $currentDate = Date::g('Y-m-d H:i:s');
            self::$db->where("TIMESTAMPDIFF(SECOND,IFNULL(update_date,insert_date),?) > ?", array($currentDate, $lifetime));
        }
        return self::$db->delete(self::session);
    }

    public static function insert($session_id, $session_data,$lifeime, $sec_token = null)
    {
        return self::$db->insert(self::session, array(
            'session_id' => $session_id,
            'app' => Session::getApp(),
            'session_data' => $session_data,
            'insert_date' => Date::g('Y-m-d H:i:s'),
            'update_date' => Date::g('Y-m-d H:i:s'),
            'ip' => HelperHeader::getIp(),
            'security_token' => $sec_token,
            'end_date' => Date::g('Y-m-d H:i:s',time() + $lifeime),
        ));
    }

    public static function update($session_id, $session_data,$lifeime)
    {
        self::$db->where('app',Session::getApp());
        self::$db->where('session_id', $session_id);
        return self::$db->update(self::session, array(
            'session_data' => $session_data,
            'update_date' => Date::g('Y-m-d H:i:s'),
            'end_date' => Date::g('Y-m-d H:i:s',time() + $lifeime),
        ));
    }

    public static function is_exists($session_id)
    {
        self::$db->where('app',Session::getApp());
        self::$db->where('session_id', $session_id);
        self::$db->getOne(self::session);
        return self::$db->count > 0 ? true : false;
    }


    public static function delete($session_id)
    {
        self::$db->where('app',Session::getApp());
        self::$db->where('session_id', $session_id);
        return self::$db->delete(self::session);
    }

    public static function delete_by_app($app)
    {
        $app = is_null($app) ? Session::getApp() : $app;

        self::$db->where('app',$app);
        return self::$db->delete(self::session);
    }
}
    
