<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\model;

use pinoox\component\database\Model;

class UserModel extends Model
{

    const active = 'active';
    const suspend = 'suspend';
    const CREATED_AT = 'register_date';
    const UPDATED_AT = null;
    protected $table = 'pincore_user';
    public $incrementing = false;
    public $primaryKey = 'user_id';

}
