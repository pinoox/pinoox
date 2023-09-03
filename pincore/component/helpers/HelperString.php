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

namespace pinoox\component\helpers;

class HelperString
{
    // get unique form one string by time,md5,exists,uniqid,...
    public static function get_unique_string($string, $decoding_type = "", $prefix = "", $postfix = "", $i_loop = "", $dir = null, $ext = null)
    {
        #change it, time..
        if ($decoding_type == "time") {
            list($usec, $sec) = explode(" ", microtime());
            $extra = str_replace('.', '', (float)$usec + (float)$sec);
            $return = $prefix . $extra . $i_loop . $postfix;
        } # md5
        elseif ($decoding_type == "md5") {
            list($usec, $sec) = explode(" ", microtime());
            $extra = md5(((float)$usec + (float)$sec) . $string);
            $extra = substr($extra, 0, 12);
            $return = $prefix . $extra . $i_loop . $postfix;
        } # exists before, change it a little
        elseif ($decoding_type == 'exists') {
            $return = $string . '_' . substr(md5(time() . $i_loop), rand(0, 20), 5) . $postfix;
            $return = $prefix . $return;
        } elseif ($decoding_type == 'uniqid') {
            $return = $prefix . uniqid($string) . $i_loop . $postfix;
        } #nothing
        else {
            $return = self::changeSignsToOneSing($string) . $i_loop . $postfix;
            $return = preg_replace('/-+/', '-', $return);
            $return = $prefix . $return;
        }

        if (!empty($dir) && !empty($ext) && is_file($dir . $return . '.' . $ext)) {
            $i_loop = !empty($i_loop) ? $i_loop : 0;
            $i_loop++;
            $return = self::get_unique_string($string, $decoding_type, $prefix, $postfix, $i_loop, $dir, $ext);
        }
        return $return;
    }

    // change all Signs To One Sing
    public static function changeSignsToOneSing($string, $sign = '-')
    {
        return preg_replace('/[,.?\/*&^\\\$%#@()_!|"\~\'><=+}{; ]/', $sign, $string);
    }

    // change all english number to persian number
    public static function convertPersianNumbers($matches)
    {
        $farsi_array = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_array = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        return str_replace($english_array, $farsi_array, $matches);
    }

    public static function format($number, $format = 2)
    {
        return number_format((float)$number, $format, '.', '');
    }


    public static function truncateText($text, $chars = 25, $stripTags = false)
    {
        if (empty($text)) return "";
        if ($stripTags) $text = strip_tags($text);

        if (strlen($text) <= $chars) return $text;
        $text = $text . " ";
        $text = substr($text, 0, $chars);
        $text = substr($text, 0, strrpos($text, ' '));
        $text = $text . "...";

        return $text;
    }

    public static function lastDelete($string, $search)
    {
        if (self::lastHas($string, $search)) {
            $string = substr($string, 0, strrpos($string, $search));
            $string = (empty($string)) ? '' : $string;
        }

        return $string;
    }

    public static function lastHas($string, $search)
    {
        if (is_array($search)) {
            foreach ($search as $s) {
                if (self::lastHas($string, $s)) {
                    return true;
                }
            }

        } else {
            if (substr($string, -strlen($search)) == $search) {
                return true;
            }
        }
        return false;
    }

    /*
     * remove extension in string
     * @params
     * $start : min length ext
     * $end : max length ext
     */

    public static function firstDelete($string, $search)
    {
        if (self::firstHas($string, $search)) {
            $string = substr($string, strlen($search));
            $string = (empty($string)) ? '' : $string;
        }

        return $string;
    }

    public static function firstHas($string, $search)
    {
        if (is_array($search)) {
            foreach ($search as $s) {
                if (self::firstHas($string, $s)) {
                    return true;
                }
            }

        } else {
            if (!empty($string) && str_starts_with($string, $search)) {
                return true;
            }
        }

        return false;
    }

    public static function deleteExt($string, $start = 1, $end = 5)
    {
        return preg_replace('/\\.[^.\\s]{' . $start . ',' . $end . '}$/', '', $string);
    }

    public static function hideLetters($string, $visibleCount, $replace = '*')
    {
        if (empty($string)) return false;
        $totalCount = strlen($string);
        $hideString = '';
        $counter = $totalCount - $visibleCount;
        while ($counter > 0) {
            $hideString .= $replace;
            $counter--;
        }
        $visible = substr($string, $totalCount - $visibleCount, $totalCount);
        return $hideString . $visible;
    }

    public static function decodeJson($json, $isArray = true)
    {
        return json_decode($json, $isArray);
    }

    public static function generateRandom($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generateLowRandom($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function camelCase($str, $type = '-')
    {
        if (!is_array($type))
            $arr = explode($type, $str);
        else
            $arr = HelperString::multiExplode($type, $str);

        $result = '';
        foreach ($arr as $index => $item) {
            $result .= ($index != 0) ? ucfirst($item) : $item;
        }
        return $result;
    }

    public static function toCamelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        return str_replace(" ", "", $str);
    }

    public static function toUnderScore($str)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $str)), '_');
    }

    public static function multiExplode(array $delimiters,string $string): array|bool
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        return explode($delimiters[0], $ready);
    }

    public static function camelToUnderscore($string, $us = "-")
    {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $us, $string));
    }

    public static function divTwoPart($string, $search, $isLastStart = false, $length = 0)
    {
        if (!HelperString::has($string, $search)) {
            $parts[0] = $string;
            $parts[1] = $search;

            return $parts;
        }

        if ($isLastStart) {
            $currentLength = strrpos($string, $search);
        } else {
            $currentLength = strpos($string, $search);
        }

        $currentLength += $length;
        $parts[0] = substr($string, 0, $currentLength);
        $parts[1] = substr($string, $currentLength);

        return $parts;
    }

    public static function has($string, $search)
    {
        if (is_array($search)) {
            foreach ($search as $s) {
                if (self::has($string, $s)) {
                    return true;
                }
            }

        } else {
            if (strpos($string, $search) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function contains($search, $text)
    {
        if (strpos($text, $search) !== false)
            return true;

        return false;
    }

    public static function replaceSpace($string, $replace = '-')
    {
        $string = trim($string);
        return preg_replace('/-+/', $replace, str_replace(" ", $replace, $string));
    }

    public static function replaceFirst($from, $to, $subject)
    {
        $from = '/' . preg_quote($from, '/') . '/';

        return preg_replace($from, $to, $subject, 1);
    }

    public static function explodeDropping($delimiter, $string)
    {
        $arr = explode($delimiter, $string);
        $result = array();
        $current = '';
        foreach ($arr as $item) {
            if (empty($current)) $current .= $item;
            else $current = $current . $delimiter . $item;
            $result[] = $current;
        }

        $result = array_reverse($result);
        return $result;
    }

    public static function replaceData()
    {
        $text = '';
        $numargs = func_num_args();
        if ($numargs < 1) return $text;

        $args = func_get_args();
        $text = $args[0];
        if (is_array($text)) return $text;
        $numargs--;
        if ($numargs < 1) return $text;
        array_shift($args);
        $replaces = $args[0];

        if (is_array($replaces)) {
            foreach ($replaces as $key => $replace) {
                $replace = is_array($replace) ? HelperString::encodeJson($replace) : $replace;
                $text = str_replace("{" . $key . "}", $replace, $text);
            }
            return $text;
        }
        for ($i = 0; $i < $numargs; $i++) {
            $replace = $args[$i];
            $replace = (is_array($replace)) ? HelperString::encodeJson($replace) : $replace;
            $text = str_replace("{" . $i . "}", $replace, $text);
        }
        return $text;
    }

    public static function encodeJson($json, $isObject = false)
    {
        if ($isObject)
            return json_encode($json, JSON_FORCE_OBJECT);
        else
            return json_encode($json);

    }

    public static function generateHash($val)
    {
        return hash('md5', $val);
    }

    public function existsExt($string, $start = 1, $end = 5)
    {
        return (preg_match('/\\.[^.\\s]{' . $start . ',' . $end . '}$/', $string));
    }

    public static function width($string = '')
    {
        if (empty($string)) return null;
        return mb_strwidth($string);
    }


    /**
     * find similar string in array of strings.
     *
     * @param $input
     * @param $words
     * @param false $showPercent
     * @return array|false|mixed
     */
    public static function closest_word($input, $words, $showPercent = false)
    {
        $morePercent = -1;
        $closest = false;
        foreach ($words as $word) {
            $lev = levenshtein($input, $word);
            similar_text($input, $word, $percent1);
            similar_text($word, $input, $percent2);
            $percent0 = (1 - $lev / max(strlen($input), strlen($closest))) * 100;
            $avg = ($percent1 + $percent2 + $percent0) / 3;
            if ($lev == 0 or $percent1 >= 95 or $percent2 >= 95) {
                $closest = $word;
                $morePercent = $avg;
                break;
            }
            if ($avg > $morePercent) {
                $closest = $word;
                $morePercent = $avg;
            }
        }
        if ($showPercent) {
            $percent = round($morePercent, 2);
            return [$closest, $percent];
        }
        return $closest;
    }

}
