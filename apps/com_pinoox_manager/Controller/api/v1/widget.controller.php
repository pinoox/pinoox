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
namespace App\com_pinoox_manager\Controller\api\v1;

use Pinoox\Component\app\AppProvider;
use Pinoox\Component\Date;
use Pinoox\Component\File;
use Pinoox\Component\Response;
use Pinoox\Component\System;

class WidgetController extends LoginConfiguration
{
    public function clock()
    {
        $isJalali = (AppProvider::get('lang') === 'fa');
        $date = $isJalali? Date::j('d F Y') : Date::g('d F Y');

        Response::json([
           'time' => time(),
           'date' => t('widget>clock.today').' '.$date,
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
    
