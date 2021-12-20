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
use Exception;

class Date
{


    /**
     * Number of days 12 months a year in world calendar
     *
     * @var array
     */
    private static $gMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    /**
     * Number of days 12 months a year in jalali calendar
     *
     * @var array
     */
    private static $jalaliMonth = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    /**
     * An array of year, month and day after becoming Jalali
     *
     * @var array
     */
    private static $jDate = array();

    /**
     * Format valid for the date
     *
     * @var string|null
     */
    private static $formatValid = null;

    /**
     * Set value default timezone if empty
     */
    public static function __constructStatic()
    {
        $timezone = ini_get('date.timezone');
        if (empty($timezone) || !date_default_timezone_get())
            date_default_timezone_set('UTC');
    }

    /**
     * Get an array of jalali dates in between one interval
     *
     * @param int|string|null $start_date
     * @param int|string|null $end_date
     * @param int $num
     * @param string $format
     * @return array
     * @throws Exception
     */
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

    /**
     * Get gregorian Date or convert jalali (datetime) to gregorian (datetime)
     *     --------------            ----       =>      ---------
     *
     * #################  Examples  #######################
     *
     * example1 ) get date now:
     * Date::g('Y/m/d'); // 2017/1/13
     *
     *
     * ----------------------------------------------------
     *
     *
     * example2 ) convert jalali date to gregorian date
     * Date::g('Y-m-d','1393-09-16',true); // 2014-12-7
     *
     *
     * ----------------------------------------------------
     *
     *
     * example3 ) get date and time now with timezone tehran
     * Date::g('Y-m-d H:i:s',null,false,'Asia/Tehran');
     * or
     * Date::g('Y-m-d H:i:s','now',false,'Asia/Tehran');
     *
     * @param string $format => for example 'Y-m-d H:i:s' => 2014-12-7 20:18:12
     * @param int|string|null $date => date() or time() or '2014-12-12' or 'now'
     * @param bool $is_convert_jalali => if true convert to jalali date
     * @param string|null $new_timezone => set new timezone
     * @param string|null $old_timezone => set old timezone
     * @return string|null
     * @throws Exception
     */
    public static function g($format = 'Y-m-d', $date = 'now', $is_convert_jalali = false, $new_timezone = null, $old_timezone = null)
    {
        if (!empty(self::$formatValid)) {
            $formatValid = self::$formatValid;
            self::$formatValid = null;
            if (!self::validate($date,$formatValid)) return null;
        }

        $old_timezone = (!empty($old_timezone)) ? $old_timezone : date_default_timezone_get();
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

            if (!$date || !is_array($date) || count($date) != 3) return self::g('Y-m-d', Date::j('Y-m-d', $oldDate), true);
            list($y, $m, $d) = array($date[0], $date[1], $date[2]);
            $jdate = self::j2g($y, $m, $d);
            $jdate = implode('-', $jdate);
            $date = str_replace($match, $jdate, $oldDate);
            $ndate = new DateTime($date, new DateTimeZone($old_timezone));
        }
        if (!empty($new_timezone)) $ndate->setTimezone(new DateTimeZone($new_timezone));

        return isset($ndate) ? $ndate->format($format) : null;
    }

    /**
     * Validation of date
     *
     * @param string $format Format accepted by date().
     * @param string $date String representing the time.
     * @return bool
     */
    public static function validate($date,$format = 'Y-m-d H:i:s')
    {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * Get jalali date or convert gregorian (datetime) to jalali (datetime)
     *
     * #################  Examples  #######################
     *
     * example1 ) get date now:
     * Date::j('Y/m/d'); // 1395/10/24
     *
     *
     * ----------------------------------------------------
     *
     *
     * example2 ) convert world date (2014-12-7) to jalali date (1393-9-16)
     * Date::j('Y-m-d','2014-12-7'); // 1393-09-16
     *
     *
     * ----------------------------------------------------
     *
     * example3 ) get date and time with default timezone server
     * Date::j('Y-m-d H:i:s',null,null); // 1395-10-24 20:17:20
     * or
     * Date::j('Y-m-d H:i:s','now',null);
     * or
     * Date::j('Y-m-d H:i:s',time(),null);
     *
     * @param string $format for example 'Y-m-d H:i:s' => 1395-10-24 23:47:20
     * @param int|string|null $date => date() or time() or '1394-12-12' or 'now'
     * @param string $timezone set timezone
     * @return string|null
     * @throws Exception
     */
    public static function j($format = 'Y-m-d', $date = 'now', $timezone = 'Asia/Tehran')
    {
        if (!empty(self::$formatValid)) {
            $formatValid = self::$formatValid;
            self::$formatValid = null;
            if (!self::validate($date,$formatValid)) return null;
        }
        if (empty($date)) $date = time();
        if (is_int($date)) $date = '@' . $date;
        $old_timezone = new DateTimeZone(date_default_timezone_get());
        $ndate = new DateTime($date, $old_timezone);

        $timezone = empty($timezone) ? date_default_timezone_get() : $timezone;
        $ndate->setTimezone(new DateTimeZone($timezone));

        list($y, $m, $d) = array($ndate->format('Y'), $ndate->format('m'), $ndate->format('d'));

        $jdate = self::g2j($y, $m, $d);
        self::$jDate = ['y' => $jdate[0], 'm' => $jdate[1], 'd' => $jdate[2]];
        $chars = (preg_match_all('/([a-zA-Z]{1})/', $format, $chars)) ? $chars[0] : array();
        foreach ($chars as $char) {
            self::jalaliFormat($char, $ndate, $format);
        }
        $ndate->setDate($jdate[0], $jdate[1], $jdate[2]);
        $format = self::setDateForJalaliDate($format);
        return isset($ndate) ? $ndate->format($format) : null;
    }

    /**
     * Gregorian to Jalali Conversion
     * Copyright (C) 2000  Roozbeh Pournader and Mohammad Toossi
     *
     * @param string|int $g_y year
     * @param string|int $g_m month
     * @param string|int $g_d day
     * @return array
     */
    private static function g2j($g_y, $g_m, $g_d)
    {
        $g_y = intval($g_y);
        $g_m = intval($g_m);
        $g_d = intval($g_d);

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

    /**
     * Division
     *
     * @param $a int
     * @param $b int
     * @return int
     */
    private static function div($a, $b)
    {
        return (int)($a / $b);
    }

    /**
     * Convert format to jalali format
     *
     * @param string $type split format
     * @param DateTime $ndate
     * @param string &$format
     */
    private static function jalaliFormat($type, $ndate, &$format)
    {
        $return = null;
        switch ($type) {
            #Month
            case 'F':
                $return = self::getFarsiMonth(self::$jDate['m']);
                break;
            case 'M':
                $return = self::getFarsiMonth(self::$jDate['m'], 3);
                break;
            case 't':
                $m = intval(self::$jDate['m']);
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
                $m = intval(self::$jDate['m']);
                $d = intval(self::$jDate['d']);
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

    /**
     * Get farsi months name
     *
     * @param string $month
     * @param int|null $lenght
     * @return bool|mixed|string
     */
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

    /**
     * Get farsi days name
     *
     * @param string $day
     * @param int|null $lenght
     * @param bool $is_num
     * @return bool|mixed|string
     */
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
        if (function_exists('mb_substr') && !is_null($lenght)) {
            return mb_substr($day[1], 0, $lenght, 'UTF-8');
        }

        return (!is_null($lenght)) ? substr($day[1], 0, $lenght) : $day[1];
    }

    // get farsi day name

    /**
     * Set date for jalali date
     *
     * @param string $format
     * @return string|mixed
     */
    private static function setDateForJalaliDate($format)
    {
        $Y = self::$jDate['y'];
        $m = (strlen(self::$jDate['m']) == 1) ? '0' . self::$jDate['m'] : self::$jDate['m'];;
        $d = (strlen(self::$jDate['d']) == 1) ? '0' . self::$jDate['d'] : self::$jDate['d'];

        $y = substr($Y, -2);
        $j = self::$jDate['d'];
        $n = self::$jDate['m'];

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
     * @param string|int $j_y year
     * @param string|int $j_m month
     * @param string|int $j_d day
     * @return array
     */
    private static function j2g($j_y, $j_m, $j_d)
    {
        $j_y = intval($j_y);
        $j_m = intval($j_m);
        $j_d = intval($j_d);

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

    /**
     * Compare a date between two date
     *
     * @param string|int|null $date
     * @param string|int|null $start_date
     * @param string|int|null $end_date
     * @param bool $isJalali
     * @param string $format
     * @return bool
     * @throws Exception
     */
    public static function compareBetween($date, $start_date, $end_date, $isJalali = false, $format = 'Y/m/d H:s:m')
    {
        if ($isJalali) {
            $date = self::g($format, $date, true);
        }
        return (self::compare($date, $start_date, '>=', $isJalali, $format) && self::compare($date, $end_date, '<=', $isJalali, $format));
    }

    /**
     * Compare date
     *
     * @param string|int|null $date1
     * @param string|int|null $date2
     * @param string $operator
     * @param bool $isJalali
     * @param string $format
     * @return bool
     * @throws Exception
     */
    public static function compare($date1, $date2, $operator, $isJalali = false, $format = 'Y-m-d H:i:s')
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
            case '!=':
                return $d1 != $d2;
            case '>':
                return $d1 > $d2;
            case '<':
                return $d1 < $d2;
            case '<=':
                return $d1 <= $d2;
            case '>=':
                return $d1 >= $d2;
        }
    }

    /**
     * Get an array of gregorian dates in between one interval
     *
     * @param int|string|null $start_date
     * @param int|string|null $end_date
     * @param string $type
     * @param string $format
     * @return array
     * @throws Exception
     */
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

    /**
     * Get days week
     *
     * @param string|int|null $date
     * @param bool $isJalali
     * @param string $format
     * @return array
     */
    public static function getDaysWeek($date, $isJalali = false, $format = 'Y-m-d')
    {
        if ($isJalali)
            $type = 'j';
        else
            $type = 'g';

        $w = intval(self::$type('w', $date));
        $result = [];
        for ($i = 0; $i <= 6; $i++) {
            $_date = (($i - $w) == 0) ? $date : $date . ' ' . ($i - $w) . ' day';
            $result[$i] = self::$type($format, $_date);
        }

        return $result;
    }

    /**
     * @param string|int|null $date
     * @return mixed|string|null
     */
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

    /**
     * Get approximate date
     *
     * @param string|int $date
     * @param bool $exactAfterDays Show the exact date after the specified number of days have elapsed
     * @param string $dateFormat format date for after days
     * @return string
     * @throws Exception
     */
    public static function approximateDate($date, $exactAfterDays = false, $dateFormat = 'Y/m/d')
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
                if ($now - $secondsTimestamp <= 0) {
                    if ($val < 60) {
                        return Lang::get('~date.some_sec_before');
                    } else {
                        if ($exactAfterDays !== false) {
                            if ((3600 * 24) * $exactAfterDays <= $val)
                                return Date::g($dateFormat, $now);
                        }
                        return $res . ' ' . Lang::get('~date.before');
                    }
                }
                return null;
            }
        }

    }

    /**
     * Filter by format valid for the date
     *
     * @param string $formatValid
     */
    public function filter($formatValid)
    {
        self::$formatValid = $formatValid;
    }
}