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

class HelperArray
{

    public static function array_depth($array, $childrenkey = "_no_children_")
    {
        if (!empty($array[$childrenkey])) {
            $array = $array[$childrenkey];
        }

        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::array_depth($value, $childrenkey) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    public static function groupByPattern($array, $pattern, $keyArray = '_KEY')
    {
        $result = [];
        foreach ($array as $key => $arr) {
            if ($keyArray == '_KEY') {
                if (isset($result[$key]))
                    $result[$key] = self::getArrayForGroupPattern($arr, $pattern, $result[$key]);
                else
                    $result[$key] = self::getArrayForGroupPattern($arr, $pattern);

            } else {
                if (isset($result[$arr[$keyArray]]))
                    $result[$arr[$keyArray]] = self::getArrayForGroupPattern($arr, $pattern, $result[$arr[$keyArray]]);
                else
                    $result[$arr[$keyArray]] = self::getArrayForGroupPattern($arr, $pattern);

            }
        }
        return $result;
    }

    public static function transformNestedArrayToFlatArray($input)
    {
        $output_array = [];
        if (is_array($input)) {
            foreach ($input as $value) {
                if (is_array($value)) {
                    $output_array = array_merge($output_array, self::transformNestedArrayToFlatArray($value));
                } else {
                    array_push($output_array, $value);
                }
            }
        } else {
            array_push($output_array, $input);
        }

        return $output_array;
    }

    private static function getArrayForGroupPattern($array, $pattern, $result = [])
    {
        foreach ($pattern as $key => $itemP) {
            $key = self::found_value_to_array_by_pattern($key,$array,$result);

            if (is_array($itemP)) {
                $result[$key][] = self::getArrayForGroupPattern($array, $itemP);
            } else {

                $value = self::found_value_to_array_by_pattern($itemP,$array,$result,$key);
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private static function found_value_to_array_by_pattern($value,$array,$result,$key = null)
    {
        if(is_callable($value))
        {
           return $value($array,$result);
        }

        $is_sum = false;
        $is_variable = false;
        if(!empty($key) && HelperString::firstHas($value,'+'))
        {
            $value = HelperString::firstDelete($value,'+');
            $is_sum = true;
        }
        if(HelperString::firstHas($value,'$'))
        {
            $value = HelperString::firstDelete($value,'$');
            $is_variable = true;
        }

        if($is_variable)
            $value = (isset($array[$value])) ? $array[$value] : null;

        if($is_sum){
            if(is_numeric($value)) $value = (isset($result[$key]))? intval($result[$key]) + intval($value) : intval($value);
            else $value = (isset($result[$key]))? $result[$key] . $value : $value;
        }

        return $value;
    }

    private static $resultArray = array();
    private static $required = true;

    public static function searchArrayByPattern($pattern, $array, $delimiter = '.')
    {
        self::$resultArray = array();
        self::$required = true;
        self::getValuesByStar($pattern, $array, $delimiter);
        return ['values' => self::$resultArray, 'required' => self::$required];
    }

    private static function getValuesByStar($pattern, $array, $delimiter = '.')
    {
        if (!is_array($pattern)) $pattern = HelperString::multiExplode($delimiter, $pattern);
        foreach ($array as $key => $value) {
            $p = $pattern;

            if (isset($p[0])) {
                $main = $p[0];
                array_shift($p);
            } else {
                continue;
            }

            $main = (is_numeric($main)) ? intval($main) : $main;
            $key = (is_numeric($key)) ? intval($key) : $key;


            if ($main === '*' || $main === $key) {
                if (empty($p)) {
                    self::$resultArray[] = $value;
                    if ($main !== '*') break;
                } else if (is_array($value)) {
                    self::getValuesByStar($p, $value);
                } else {
                    self::$required = false;
                }
            } else if (empty($p)) {
                if (!isset($array[$main]))
                    self::$required = false;
            }

        }
    }

    public static function removeValueByNestedKey(&$array, $keys)
    {
        if (empty($keys)) return;

        if (count($keys) == 1) {
            if (isset($array[$keys[0]])) {
                unset($array[$keys[0]]);
                return;
            }
        }
        foreach ($keys as $k) {
            if (isset($array[$k])) {
                array_shift($keys);
                self::removeValueByNestedKey($array[$k], $keys);
            }
        }
    }

    public static function isExistsValueByNestedKey($array, $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key]))
                $array = $array[$key];
            else
                return false;
        }
        return true;
    }

    public static function getValueByNestedKey($array, $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key]))
                $array = $array[$key];
            else
                return null;
        }
        return $array;
    }

    public static function convertToArrayJavascript($array)
    {
        $result = "[";
        $isFirst = true;
        foreach ($array as $item)
        {
            if(!$isFirst) $result .= ',';
            if(is_array($item)) {
                $item = self::convertToObjectJavascript($item);
            }
            else if(!is_numeric($item))
            {
                $item = "'".$item."'";
            }

            $result .= $item;
            $isFirst = false;
        }
        $result .= "]";
        return $result;
    }

    public static function convertToObjectJavascript($array)
    {
        $result = "{";
        $isFirst = true;
        foreach ($array as $key=>$item)
        {
            if(!$isFirst) $result .= ',';
            if(is_array($item)) {
                $item = self::convertToObjectJavascript($item);
            }
            else if(!is_numeric($item))
            {
                $item = "'".$item."'";
            }

            $key = (is_numeric($key))? $key: "'".$key."'";
            $result .= $key.":".$item;
            $isFirst = false;
        }
        $result .= "}";
        return $result;
    }

    /**
     * @param $keys : string, array
     * Examples:
     * @param $keys = 'userName, city'; // without defaults
     * @param $keys = 'userName, city=tehran';// set default for specifics
     * @param $keys = array('userName','city'=>'tehran');//set default for those haven't defaults
     *
     * @param $default : string, float, int
     * @param $validation : validation
     * @param $removeNull : boolean  if value is null remove it from return array
     *
     * @param $array $_POST or $_GET
     * @return array
     */
    public static function parseParams($array, $keys, $default = null, $validation = null, $removeNull = false)
    {
        $data = [];
        if($keys == '*') $keys = (!empty($array) && is_array($array)) ? array_keys($array) : $array;
        if (is_array($keys)) {
            foreach ($keys as $key => $val) {
                // set default for array items
                if (!is_numeric($key)) {
                    $isHtml = HelperString::has($key, '!') ? true : false;
                    $key = is_string($key) ? str_replace('!', '', $key) : $key;
                    //has default
                    if ($removeNull && !isset($array[$key]))
                        continue;

                    //check is array
                    if (isset($array[$key]) && is_array($array[$key])) {
                        $data[$key] = $array[$key];
                    } else {
                        $value = isset($array[$key]) && !is_null($array[$key]) ? $array[$key] : $val;
                        $data[$key] = is_array($value) || $isHtml ? $value : (is_string($value) ? htmlspecialchars(stripslashes($value)) : $value);
                        if (!empty($validation)) {
                            if (!Validation::checkOne($data[$key], $validation))
                                $data[$key] = $val;
                        }
                    }
                } else {
                    $isHtml = HelperString::has($val, '!') ? true : false;
                    $val = is_string($val) ? str_replace('!', '', $val) : $val;
                    //there isn't default
                    if ($removeNull && !isset($array[$val]))
                        continue;
                    //check is array
                    if (isset($array[$val]) && is_array($array[$val])) {
                        $data[$val] = $array[$val];
                    } else {
                        $value = isset($array[$val]) && !is_null($array[$val]) ? $array[$val] : $default;
                        $data[$val] = $isHtml || is_array($value) ? $value : (is_string($value) ? htmlspecialchars(stripslashes($value)) : $value);
                        if (!empty($validation)) {
                            if (!Validation::checkOne($data[$val], $validation))
                                $data[$val] = $default;
                        }
                    }

                }
            }
            return $data;
        } else {
            $explodedKeys = explode(',', $keys);
            foreach ($explodedKeys as $key) {

                if (strstr($key, '=')) {
                    $cleanKey = substr($key, 0, strpos($key, '='));
                    $cleanDefault = str_replace($cleanKey . "=", '', $key);
                } else {
                    $cleanKey = $key;
                    $cleanDefault = $default;
                }
                $isHtml = HelperString::has($cleanKey, '!') ? true : false;
                $cleanKey = is_string($cleanKey) ? str_replace('!', '', $cleanKey) : $cleanKey;

                if ($removeNull && !isset($array[$cleanKey]))
                    continue;
                //check is array
                if (isset($array[$cleanKey]) && is_array($array[$cleanKey])) {
                    $data[$cleanKey] = $array[$cleanKey];
                } else {
                    $value = isset($array[$cleanKey]) && !is_null($array[$cleanKey]) ? $array[$cleanKey] : $cleanDefault;
                    $value = $isHtml ? $value : htmlspecialchars(stripslashes($value));
                    $data[$cleanKey] = $value;
                    if (!empty($validation)) {
                        if (!Validation::checkOne($data[$cleanKey], $validation))
                            $data[$cleanKey] = $cleanDefault;
                    }
                }
            }
            return $data;
        }
    }

    public static function parseParam($array, $key, $default = null, $validation = null)
    {
        $isHtml = HelperString::has($key, '!') ? true : false;
        $key = is_string($key) ? str_replace('!', '', $key) : $key;
        $value = isset($array[$key]) && !is_null($array[$key]) ? $array[$key] : $default;
        $value = $isHtml || is_array($value) ? $value : (is_string($value) ? htmlspecialchars(stripslashes($value)) : $value);


        if (!empty($validation)) {
            if (!Validation::checkOne($value, $validation))
                $value = $default;
        }

        return $value;
    }


    public static function sort_week($weeks){
        $sort=[];
        $pattern = array('sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri');
        foreach ($pattern as $v) {
            //If the value in the template exists as a key in the actual array.. (condition)
            if (array_key_exists($v, $weeks)) {
                $sort[$v] = $weeks[$v]; //The value is assigned to the new array and the key of the actual array is assigned as a value to the new array
            }
        }
        return $sort;
    }

    public static function lastKey($arr)
    {
        end($arr);
        return key($arr);
    }

    public static function flip($array,$isArray = true,$isMultiple = true)
    {
        if(!$isMultiple)
            return array_flip($array);

        $result = [];
        foreach ($array as $key=>$value)
        {

            if(empty($value) || !(is_numeric($value) || is_string($value)))
                continue;

            if($isArray || isset($result[$value]))
            {
                if(!is_array($result[$value]))
                    $result[$value] = [$result[$value]];

                $result[$value][] = $key;
            }
            else
            {
                $result[$value] = $key;
            }
        }

        return $result;
    }
}