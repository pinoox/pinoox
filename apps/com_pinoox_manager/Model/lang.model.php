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

namespace App\com_pinoox_manager\Model;

use Pinoox\Model\PinooxDatabase;

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