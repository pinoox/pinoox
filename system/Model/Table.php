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

namespace Pinoox\System\Model;

use Pinoox\Portal\Database\DB;

class Table
{
    const USER = 'user';
    const FILE = 'file';
    const TOKEN = 'token';
    const MIGRATION = 'migration';

    public static function __callStatic(string $name, array $arguments)
    {
        $alias = $arguments[0] ?? $name;
        $name = strtoupper($name);
        $table = DB::tableName(constant(static::class . '::' . $name), 'pincore');
        if ($alias) {
            $table .= ' AS ' . $alias;
        }

        return $table;
    }
}
