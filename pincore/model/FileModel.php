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
use pinoox\component\HelperString;
use pinoox\component\Router;

class FileModel extends PinooxDatabase
{
    public static function fetch_by_id($file_id)
    {
        self::$db->where('file_id', $file_id);
        return self::$db->getOne(self::file);
    }

    public static function fetch_by_id_group_name_ext($id, $name, $ext, $group)
    {
        self::$db->where('file_id', $id);
        self::$db->where('file_realname', $name . '.' . $ext);
        self::$db->where('file_ext', $ext);
        self::$db->where('file_group', $group);
        return self::$db->getOne(self::file);
    }

    public static function insert($option)
    {
        return self::$db->insert(self::file, array(
            "user_id" => $option['user_id'],
            "app" => Router::getApp(),
            "file_group" => $option['group'],
            "file_realname" => HelperString::replaceSpace($option['realname'], '_'),
            "file_name" => $option['uploadname'],
            "file_ext" => $option['ext'],
            "file_path" => $option['dir_file'],
            "file_size" => $option['size'],
            "file_date" => Date::g('Y-m-d H:i:s'),
            "file_access" => $option['access']
        ));
    }

    public static function update($option)
    {
        $arr = array(
            "file_realname" => HelperString::replaceSpace($option['realname'], '_'),
            "file_name" => $option['uploadname'],
            "file_ext" => $option['ext'],
            "file_path" => $option['dir_file'],
            "file_size" => $option['size'],
            "file_date" => Date::g('Y-m-d H:i:s')
        );

        if (isset($option['access'])) {
            $arr['file_access'] = $option['access'];
        }
        if (isset($option['user_id'])) {
            $arr['user_id'] = $option['user_id'];
        }
        if (isset($option['app'])) {
            $arr['app'] = $option['app'];
        }
        if (isset($option['group'])) {
            $arr['file_group'] = $option['group'];
        }
        self::$db->where('file_id', $option['file_id']);
        return self::$db->update(self::file, $arr);
    }

    public static function delete($file_id)
    {
        self::$db->where('file_id', $file_id);
        return self::$db->delete(self::file);
    }

    public static function delete_group($file_ids)
    {
        self::$db->where('file_id', $file_ids, 'IN');
        return self::$db->delete(self::file);
    }
}
