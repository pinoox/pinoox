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

namespace pinoox\component;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;

class Date
{
    use MagicTrait;

    private static $gMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    private static $jalaliMonth = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    private static $date = array();
    private static $formatValid = null;

    public function __construct($formatValid)
    {
        self::$formatValid = $formatValid;
    }

    /*
     * get Iran Date or convert Gregorian (datetime) to jalali (datetime)
     *     ---------            ---------       =>      -----
     *
     * #################  Examples  #######################
     *
     * example1 ) get date now:
     * Date::jDate('Y/m/d H:i:s'); // 1395/10/24 23:47:20
     *
     *
     * ----------------------------------------------------
     *
     *
     * example2 ) convert date 2014-12-7 to jalali date
     * Date::jDate('Y-m-d','2014-12-7'); // 1393-09-16
     *
     *
     * ----------------------------------------------------
     *
     *
     * example3 ) get date and time now but without time Tehran and get time Gregorian
     * Date::jDate('Y-m-d H:i:s',null,null); // 1395-10-24 20:17:20
     * or
     * Date::jDate('Y-m-d H:i:s','now',null);
     * or
     * Date::jDate('Y-m-d H:i:s',time(),null);
     *
     * -------------------------------------------------------------
     * Date::jDate(1,2,3)
     * 1) Format : 'Y-m-d
     * 2) date = date() or time() or '1394-12-12' or 'now'
     * 3) timezone = set timezone if null = 'Asia/Tehran';
     *
     */

    public static function __init()
    {
        $timezone = ini_get('date.timezone');
        if (empty($timezone) || !date_default_timezone_get())
            date_default_timezone_set('UTC');
    }

    public static function betweenJDate($start_date, $end_date, $num = 1, $format = 'Y-m-d')
    {
        $begin = self::g('Y-m-d H:i:s', $start_date, true);
        $begin = new DateTime($begin);
        $end = self::g('Y-m-d H:i:s', $end_date, true);
        $end = new DateTime($end);

        $dateRange = new DatePeriod($begin, new DateInterval('P' . $num . 'D'), $end);
        $result = [];
        foreach ($dateRange as $date) {
            $result[] = self::j($format, $date->format('Y-m-d H:i:s'));
        }

        return $result;
    }


    /*
     * get Gregorian Date or convert jalali (datetime) to Gregorian (datetime)
     *     --------------            ----       =>      ---------
     *
     * #################  Examples  #######################
     *
     * example1 ) get date now:
     * Date::gDate('Y/m/d H:i:s'); // 2017/1/13 23:47:20
     *
     *
     * ----------------------------------------------------
     *
     *
     * example2 ) convert date 1393-09-16 to Gregorian date
     * Date::gDate('Y-m-d','1393-09-16',true); // 2014-12-7
     *
     *
     * ----------------------------------------------------
     *
     *
     * example3 ) get date and time now but without time Gregorian and get time Tehran
     * Date::gDate('Y-m-d H:i:s',null,false,'Asia/Tehran');
     * or
     * Date::gDate('Y-m-d H:i:s','now',false,'Asia/Tehran');
     * or
     * Date::gDate('Y-m-d H:i:s',time(),false,'ir');
     *
     * ('Asia/Tehran' == 'ir')
     * -------------------------------------------------------------
     * Date::gDate(1,2,3,4,5)
     * 1) Format : 'Y-m-d
     * 2) convert to jalali = true if no = false
     * 3) date = date() or time() or '2014-12-12' or 'now'
     * 4) timezone = set timezone
     * 5) old timezone
     *
     */

    public static function g($format = 'Y-m-d', $date = 'now', $is_convert_jalali = false, $new_timezone = null, $old_timezone = null)
    {
        if (!empty(self::$formatValid)) {
            $formatValid = self::$formatValid;
            self::$formatValid = null;
            if (!self::validate($formatValid, $date)) return null;
        }

        $old_timezone = ((strtolower($old_timezone) == 'ir') ? 'Asia/Tehran' : $old_timezone);
        $old_timezone = (!empty($old_timezone)) ? $old_timezone : date_default_timezone_get();
        $new_timezone = (strtolower($new_timezone) == 'ir') ? 'Asia/Tehran' : $new_timezone;
        if (empty($date)) $date = time();
        if (is_int($date)) $date = '@' . $date;
        $ndate = new DateTime($date, new DateTimeZone($old_timezone));

        if ($is_convert_jalali) {
            preg_match('/\d{2,4}[-|\/]\d{1,2}[-|\/]\d{1,2}/', $date, $match);
            if (isset($match[0]))
                $match = $match[0];
            else
                return self::g('Y-m-d', Date::j('Y-m-d', $date), true);


            $oldDate = $date;
            $date = preg_split('/[\-|\/]+/', $match);

            if (count($date) != 3) self::g('Y-m-d', Date::j('Y-m-d', $oldDate), true);
            list($y, $m, $d) = array($date[0], $date[1], $date[2]);
            $jdate = self::j2g($y, $m, $d);
            $jdate = implode('-', $jdate);
            $date = str_replace($match, $jdate, $oldDate);
            $ndate = new DateTime($date, new DateTimeZone($old_timezone));
        }
        if (!empty($new_timezone)) $ndate->setTimezone(new DateTimeZone($new_timezone));

        return isset($ndate) ? $ndate->format($format) : null;
    }

    public static function validate($format = 'Y-m-d H:i:s', $date = null)
    {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function j($format = 'Y-m-d', $date = 'now', $timezone = 'Asia/Tehran')
    {
        if (!empty(self::$formatValid)) {
            $formatValid = self::$formatValid;
            self::$formatValid = null;
            if (!self::validate($formatValid, $date)) return null;
        }
        if (empty($date)) $date = time();
        if (is_int($date)) $date = '@' . $date;
        $old_timezone = new DateTimeZone(date_default_timezone_get());
        $ndate = new DateTime($date, $old_timezone);

        $timezone = empty($timezone) ? date_default_timezone_get() : $timezone;
        $ndate->setTimezone(new DateTimeZone($timezone));

        list($y, $m, $d) = array($ndate->format('Y'), $ndate->format('m'), $ndate->format('d'));

        $jdate = self::g2j($y, $m, $d);
        self::$date = ['y' => $jdate[0], 'm' => $jdate[1], 'd' => $jdate[2]];
        $chars = (preg_match_all('/([a-zA-Z]{1})/', $format, $chars)) ? $chars[0] : array();
        foreach ($chars as $char) {
            self::jalaliFormat($char, $ndate, $format);
        }
        $ndate->setDate($jdate[0], $jdate[1], $jdate[2]);
        $format = self::setDateForIranDate($format);
        return isset($ndate) ? $ndate->format($format) : null;
    }

    /**
     * Gregorian to Jalali Conversion
     * Copyright (C) 2000  Roozbeh Pournader and Mohammad Toossi
     */
    private static function g2j($g_y, $g_m, $g_d)
    {

        $g_days_in_month = self::$gMonth;
        $j_days_in_month = self::$jalaliMonth;

        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;

        $g_day_no = 365 * $gy + self::div($gy + 3, 4) - self::div($gy + 99, 100) + self::div($gy + 399, 400);

        for ($i = 0; $i < $gm; ++$i)
            $g_day_no += $g_days_in_month[$i];
        if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)))
            $g_day_no++;
        $g_day_no += $gd;

        $j_day_no = $g_day_no - 79;

        $j_np = self::div($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;

        $jy = 979 + 33 * $j_np + 4 * self::div($j_day_no, 1461);

        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $jy += self::div($j_day_no - 1, 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
            $j_day_no -= $j_days_in_month[$i];
        $jm = $i + 1;
        $jd = $j_day_no + 1;

        return array($jy, $jm, $jd);

    }

    // set format for jalali date

    /**
     * Division
     */
    private static function div($a, $b)
    {
        return (int)($a / $b);
    }

    private static function jalaliFormat($type, $ndate, &$format)
    {
        $return = null;
        switch ($type) {
            #Month
            case 'F':
                $return = self::getFarsiMonth(self::$date['m']);
                break;
            case 'M':
                $return = self::getFarsiMonth(self::$date['m'], 3);
                break;
            case 't':
                $m = intval(self::$date['m']);
                $return = self::$jalaliMonth[$m - 1];
                break;

            #Day
            case 'l':
                $return = self::getFarsiDay($ndate->format('l'));
                break;
            case 'D':
                $return = self::getFarsiDay($ndate->format('D'), 3);
                break;
            case 'N':
                $return = self::getFarsiDay($ndate->format('l'), null, true);
                break;
            case 'w':
                $return = self::getFarsiDay($ndate->format('l'), null, true) - 1;
                break;
            case 'z':
                $m = intval(self::$date['m']);
                $d = intval(self::$date['d']);
                $months = [0, 31, 62, 93, 124, 155, 186, 216, 246, 276, 306, 336];
                $day = $months[$m - 1];
                $day += $d;
                $return = $day;
                break;

            #Week
            case 'W':
                $_format = 'z';
                self::jalaliFormat('z', $ndate, $_format);
                $day = intval($_format);
                $week = (int)floor($day / 7);
                $return = $week;
                break;

            #Time
            case 'a':
                $return = ($ndate->format('a') == 'am') ? 'ق.ظ' : 'ب.ظ';
                break;
            case 'A':
                $return = ($ndate->format('A') == 'AM') ? 'قبل از ظهر' : 'بعد از ظهر';
                break;

            #Full Date
            case 'r':
                $_format = 'D, d M Y H:i:s P';
                self::jalaliFormat('D', $ndate, $_format);
                self::jalaliFormat('M', $ndate, $_format);
                $return = $ndate->format($_format);
                break;

            #Orher
            case 'S':
                $return = 'ام';
                break;

        }
        if (!is_null($return)) $format = str_replace($type, $return, $format);
    }

    // get farsi month name

    private static function getFarsiMonth($month, $lenght = null)
    {
        $month--;
        $months = array(
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        );

        $month = $months[$month];
        if (function_exists('mb_substr') && $lenght != null) {
            return mb_substr($month, 0, $lenght, 'UTF-8');
        }

        return ($lenght != null) ? substr($month, 0, $lenght) : $month;
    }

    // get farsi day name
    private static function getFarsiDay($day, $lenght = null, $is_num = false)
    {
        $days = array(
            'sat' => array(1, 'شنبه'),
            'sun' => array(2, 'یکشنبه'),
            'mon' => array(3, 'دوشنبه'),
            'tue' => array(4, 'سه شنبه'),
            'wed' => array(5, 'چهارشنبه'),
            'thu' => array(6, 'پنجشنبه'),
            'fri' => array(7, 'جمعه')
        );

        $day = substr(strtolower($day), 0, 3);
        $day = $days[$day];
        if ($is_num) {
            return $day[0];
        }
        if (function_exists('mb_substr') && $lenght != null) {
            return mb_substr($day[1], 0, $lenght, 'UTF-8');
        }

        return ($lenght != null) ? substr($day[1], 0, $lenght) : $day[1];
    }

    private static function setDateForIranDate($format)
    {
        $Y = self::$date['y'];
        $m = (strlen(self::$date['m']) == 1) ? '0' . self::$date['m'] : self::$date['m'];;
        $d = (strlen(self::$date['d']) == 1) ? '0' . self::$date['d'] : self::$date['d'];

        $y = substr($Y, -2);
        $j = self::$date['d'];
        $n = self::$date['m'];

        $format = str_replace('Y', $Y, $format);
        $format = str_replace('m', $m, $format);
        $format = str_replace('n', $n, $format);
        $format = str_replace('y', $y, $format);
        $format = str_replace('d', $d, $format);
        $format = str_replace('j', $j, $format);

        return $format;

    }

    /**
     * Jalali to Gregorian Conversion
     * Copyright (C) 2000  Roozbeh Pournader and Mohammad Toossi
     *
     */
    private static function j2g($j_y, $j_m, $j_d)
    {
        $g_days_in_month = self::$gMonth;
        $j_days_in_month = self::$jalaliMonth;

        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;

        $j_day_no = 365 * $jy + self::div($jy, 33) * 8 + self::div($jy % 33 + 3, 4);
        for ($i = 0; $i < $jm; ++$i)
            $j_day_no += $j_days_in_month[$i];

        $j_day_no += $jd;

        $g_day_no = $j_day_no + 79;

        $gy = 1600 + 400 * self::div($g_day_no, 146097);
        $g_day_no = $g_day_no % 146097;

        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * self::div($g_day_no, 36524);
            $g_day_no = $g_day_no % 36524;

            if ($g_day_no >= 365)
                $g_day_no++;
            else
                $leap = false;
        }

        $gy += 4 * self::div($g_day_no, 1461);
        $g_day_no %= 1461;

        if ($g_day_no >= 366) {
            $leap = false;

            $g_day_no--;
            $gy += self::div($g_day_no, 365);
            $g_day_no = $g_day_no % 365;
        }

        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++)
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
        $gm = $i + 1;
        $gd = $g_day_no + 1;

        return array($gy, $gm, $gd);

    }

    public static function compareBetween($date, $start_date, $end_date, $isJalali = false, $format = 'Y/m/d H:s:m')
    {
        if ($isJalali) {
            $date = self::g($format, $date, true);
        }
        return (self::compare($date, $start_date, '>=', $isJalali, $format) && self::compare($date, $end_date, '<=', $isJalali, $format));
    }

    public static function compare($date1, $date2, $operator, $isJalali = false, $format = 'Y/m/d H:s:m')
    {
        if ($isJalali) {
            $date1 = self::g($format, $date1, true);
            $date2 = self::g($format, $date2, true);
        }
        $d1 = new DateTime($date1);
        $d2 = new DateTime($date2);
        switch ($operator) {
            case '==':
                return $d1 == $d2;
                break;
            case '!=':
                return $d1 != $d2;
                break;
            case '>':
                return $d1 > $d2;
                break;
            case '<':
                return $d1 < $d2;
                break;
            case '<=':
                return $d1 <= $d2;
                break;
            case '>=':
                return $d1 >= $d2;
                break;
        }
    }

    public static function getWeekJalali($start_date, $week, $end_date = 'now')
    {
        if (empty($week) || $week < 0 || !self::compare($start_date, $end_date, '<=')) return 0;
        if ($week == 1) return 1;
        $numSaturday = 6 - intval(self::j('w', $start_date));
        $numSaturday = '+' . $numSaturday . ' day';
        $firstSaturday = self::g('Y-m-d', $start_date . ' ' . $numSaturday);
        $SaturdaysWeek = self::betweenGDate($firstSaturday, $end_date, '1W');
        $count = count($SaturdaysWeek) + 1;
        if ($week >= $count) return $count;
        $numWeek = intval($count % $week);
        return ($numWeek == 0) ? $week : $numWeek;
    }

    public static function betweenGDate($start_date, $end_date, $type = '1D', $format = 'Y-m-d')
    {
        $begin = new DateTime($start_date);
        $end = new DateTime($end_date);

        $dateRange = new DatePeriod($begin, new DateInterval('P' . $type), $end);
        $result = [];
        foreach ($dateRange as $date) {
            $result[] = $date->format($format);
        }

        return $result;
    }

    public static function getDaysWeekByDate($date, $isJalali = false, $format = 'Y-m-d')
    {
        if ($isJalali)
            $type = 'jDate';
        else
            $type = 'gDate';

        $w = intval(self::$type('w', $date));
        $result = [];
        for ($i = 0; $i <= 6; $i++) {
            $_date = (($i - $w) == 0) ? $date : $date . ' ' . ($i - $w) . ' day';
            $result[$i] = self::$type($format, $_date);
        }

        return $result;
    }

    public static function dayOfWeek($date = null)
    {
        $week = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        if (is_null($date)) {
            $day = strtolower(date('D'));
        } else {
            $index = date('w', strtotime($date));
            $day = isset($week[$index]) ? $week[$index] : null;
        }
        return $day;
    }

    public static function approximateDate($date)
    {
        $units = array(
            Lang::get('~date.year') => 3600 * 24 * 365,//one year
            Lang::get('~date.month') => 3600 * 24 * 30,//one month
            Lang::get('~date.week') => 3600 * 24 * 7,//one week
            Lang::get('~date.day') => 3600 * 24,//one day
            Lang::get('~date.hour') => 3600,//one hour
            Lang::get('~date.minute') => 60,//one minute
            Lang::get('~date.second') => 1,//one second
        );

        $secondsTimestamp = strtotime("now");
        $now = strtotime($date);
        if (!$secondsTimestamp)
            return "Date isn't valid";

        foreach ($units as $key => $val) {
            $elapsed = round(abs($now - $secondsTimestamp));
            if ($elapsed >= $val) {
                $res = (round($elapsed / $val) . " " . $key);
                if ($now - $secondsTimestamp > 0) {
                    return $res . ' ' . Lang::get('TILL');
                } else {
                    return $res . ' ' . Lang::get('ELAPSED');
                }

            }
        }

    }

}