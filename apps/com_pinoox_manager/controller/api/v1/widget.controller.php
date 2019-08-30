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
namespace pinoox\app\com_pinoox_manager\controller\api\v1;

use pinoox\component\app\AppProvider;
use pinoox\component\Date;
use pinoox\component\File;
use pinoox\component\Response;
use pinoox\component\System;

class WidgetController extends LoginConfiguration
{
    public function clock()
    {
        $isJalali = (AppProvider::get('lang') === 'fa');
        $date = $isJalali? Date::j('d F Y') : Date::g('d F Y');

        Response::json([
           'time' => time(),
           'date' => rlang('widget>clock.today').' '.$date,
           'moment' => $isJalali? Date::j('a') :  Date::g('a'),
       ]);
    }

    public function storage()
    {
        $totalSpace = System::totalSpace();
        $freeSpace = System::freeSpace();
        $useSpace = System::useSpace();
        $percent = round($useSpace / ($totalSpace / 100));

        Response::json([
            'total' => round($totalSpace, 1),
            'free' => round($freeSpace, 1),
            'use' => round($useSpace, 1),
            'percent' => $percent,
        ]);
    }
}
    
