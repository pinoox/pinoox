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

namespace Pinoox\Model;

class Table
{
    private const PREFIX = 'pincore_';

    const USER = self::PREFIX . 'user';
    const FILE = self::PREFIX . 'file';
    const TOKEN = self::PREFIX . 'token';
    const MIGRATION = self::PREFIX . 'migration';

    public static function __callStatic(string $name, array $arguments)
    {
        $alias = $arguments[0] ?? $name;
        $name = strtoupper($name);
        $table = constant(static::class . '::' . $name);
        if ($alias) {
            $table .= ' AS ' . $alias;
        }

        return $table;
    }
}