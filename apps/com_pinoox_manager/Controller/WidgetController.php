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


namespace App\com_pinoox_manager\Controller;


use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Pinoox\Component\Date;
use Pinoox\Component\File;

class WidgetController extends Api
{
    public function clock()
    {
        if ((app('lang') === 'fa')) {
            $now = Jalalian::now(new \DateTimeZone('Asia/Tehran'));
        } else {
            $now = Carbon::now();
        }

        return [
            'time' => time(),
            'date' => t('widget/clock.today') . ' ' . $now->format('d F Y'),
            'moment' => $now->format('a'),
        ];
    }

    public function storage()
    {
        $totalSpace = $this->totalSpace();
        $freeSpace = $this->freeSpace();
        $useSpace = $this->useSpace();
        $percent = round($useSpace / ($totalSpace / 100));

        return [
            'total' => round($totalSpace, 1),
            'free' => round($freeSpace, 1),
            'use' => round($useSpace, 1),
            'percent' => $percent,
        ];
    }


    private function freeSpace($unit = 'GB', $round = 1)
    {
        $freeSpace = disk_free_space(path('~'));
        return File::convert_size($freeSpace, 'B', $unit, $round);
    }

    private function totalSpace($unit = 'GB', $round = 1)
    {
        $totalSpace = disk_total_space(path('~'));
        return File::convert_size($totalSpace, 'B', $unit, $round);
    }

    private function useSpace($unit = 'GB', $round = 1)
    {
        return self::totalSpace($unit, $round) - self::freeSpace($unit, $round);
    }
}