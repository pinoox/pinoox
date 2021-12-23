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

use pinoox\model\PinooxDatabase;

class LangModel extends PinooxDatabase
{

    public static function fetch_all()
    {
        return [
            'manager' => rlang('manager'),
            'user' => rlang('user'),
            'setting' => [
                'account' => rlang('setting>account'),
                'dashboard' => rlang('setting>dashboard'),
                'market' => rlang('setting>market'),
                'router' => rlang('setting>router'),
                'appManager' => rlang('setting>appManager'),
            ],
            'widget' => [
                'clock' => rlang('widget>clock'),
                'storage' => rlang('widget>storage'),
            ],
        ];
    }
}