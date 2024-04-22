<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       pinoox.com
 * @copyright  pinoox
 */

namespace Pinoox\Component\Database;

use Illuminate\Contracts\Database\Query\Expression as ObjectPortal4;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

/**
 * @mixin Connection
 */
class DatabaseManager extends Capsule
{
    public function setPrefix(string $prefix): void
    {
        $this->getConnection()->setTablePrefix($prefix);
    }

    public function orderColumn(array|string $field): string|ObjectPortal4
    {
        if (is_array($field)) {
            return $this->raw("CONCAT(" . implode(', \' \', ', $field) . ")");
        }

        return $field;
    }

    public function orderDirection(string $type): string
    {
        return strtolower($type) === 'asc' ? 'asc' : 'desc';
    }
}