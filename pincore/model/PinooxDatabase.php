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

    const session = 'pincore_session';
    const user = 'pincore_user';
    const file = 'pincore_file';
    const token = 'pincore_token';

    private static $config = [];

    public static function __constructStatic()
    {
        self::$config = Config::get('~database');
        self::$db = new DB(
            self::$config['host'],
            self::$config['username'],
            self::$config['password'],
            self::$config['database'],
            null,
            null,
            'utf8mb4'
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

    public static function getTables($app = null)
    {
        //$query = 'SHOW TABLES';
        //$query = (!empty($app))? $query.' LIKE "'.self::$db->escape($app).'%"' : $query;
        if(!empty($app))
            self::$db->where('table_name',$app.'%','LIKE');
        self::$db->where('table_schema', self::$config['database']);
        $result = self::$db->get('information_schema.TABLES',null, 'table_name');
        $result = array_column($result,'table_name');

        return $result;
    }
}
    
