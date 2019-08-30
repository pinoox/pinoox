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

use pinoox\component\Config;
use pinoox\component\DB;
use pinoox\component\MagicTrait;
use pinoox\component\source\Database;

class PinooxDatabase extends Database
{
    use MagicTrait;

    const session = 'session';
    const user = 'user';
    const file = 'file';
    const notification = 'notification';

    public static function __init()
    {
        $db_configs = Config::get('~database');
        self::$db = new DB(
            $db_configs['host'],
            $db_configs['username'],
            $db_configs['password'],
            $db_configs['database'],
            $db_configs['prefix']
        );
    }


    public static function startTransaction()
    {
        self::$db->startTransaction();
    }

    public static function commit()
    {
        self::$db->commit();
    }

    public static function rollback()
    {
        self::$db->rollback();
    }

    public static function stopQuery()
    {
        self::$db->setTrace(true);
    }

    public static function printQuery()
    {
        return self::$db->trace;
    }

}
    
