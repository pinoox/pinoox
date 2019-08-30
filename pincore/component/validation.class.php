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

class Validation
{
    private static $obj;
    private static $inputs;
    private static $rules;
    private static $customMessages;
    private static $listMethodValidators;
    private static $errors;
    private static $isFail;
    private static $data;
    private static $field_title;
    private static $currentKey;
    private static $isNot = false;
    private static $activeMethod;
    private static $isErr = false;
    private static $resultMethod = true;
    private static $params = [];
    private static $validators = [];
    private static $field_titles = [];
    private static $field_types = [];
    private static $is_required = false;

    public static function check($inputs, $rules, $messages = null)
    {
        self::$isErr = true;
        self::$inputs = $inputs;
        self::$rules = $rules;
        self::$customMessages = $messages;

        self::init();
        self::validateInputsByRules();

        return self::$obj;
    }

    private static function init()
    {
        if (empty(self::$obj))
            self::$obj = new Validation();
        self::getDefinedValidators();
        self::setFail(false);
    }

    private static function getDefinedValidators()
    {
        if (!empty(self::$listMethodValidators)) return;

        $class = new \ReflectionClass(self::class);
        $methods = $class->getMethods(
            \ReflectionMethod::IS_PUBLIC |
            \ReflectionMethod::IS_PROTECTED |
            \ReflectionMethod::IS_PRIVATE
        );
        foreach ($methods as $method) {
            $mName = $method->name;
            if (substr($mName, 0, 1) == '_') {
                self::$listMethodValidators[] = $mName;
            }
        }
    }

    private static function setFail($status = true)
    {
        self::$isFail = $status;
    }

    private static function validateInputsByRules()
    {
        foreach (self::$rules as $key => $conditions) {
            self::$currentKey = $key;
            if (is_array($conditions)) {
                $part_conds = $conditions[0];
                self::$field_title = isset($conditions[1]) ? $conditions[1] : '';
            } else {
                $part_conds = $conditions;
            }

            self::$field_titles[$key] = self::$field_title;

            self::$data = HelperArray::searchArrayByPattern($key, self::$inputs);

            $condParts = explode('|', $part_conds);
            self::$field_types[$key] = $condParts;
            self::$is_required = in_array('required', $condParts);
            foreach ($condParts as $cond) {
                self::executeRuleMethod($cond);
            }
        }
    }

    private static function executeRuleMethod($ruleName)
    {
        $params = array();
        $method = self::getMethodName($ruleName);

        //check for exist parameters
        if (HelperString::has($method, ':')) {
            $parts = explode(':', $method);
            $method = isset($parts[0]) ? $parts[0] : null;
            $params = isset($parts[1]) ? $parts[1] : array();
            //check for multi parameters
            if (!empty($params)) {
                $params = explode(',', $params);
            }
        }
        if (empty($params)) $params = array();

        self::$params = $params;

        if (isset(self::$validators[$method])) {
            $validator = self::$validators[$method];
            self::executeRuleGenerate($validator['status'], $validator['err'], $validator['dataIntoMessage']);
        } else if (self::isValidCountParams(self::class, $method, $params)) {
            self::$activeMethod = $method;
            self::callMethod(self::class, $method, $params);
        } else {
            //give an error...
        }
    }

    private static function getMethodName($ruleName)
    {
        self::$isNot = false;
        $ruleName = trim($ruleName);

        if (HelperString::firstHas($ruleName, '!')) {
            $ruleName = HelperString::firstDelete($ruleName, '!');
            self::$isNot = true;
        }

        $method = '_' . $ruleName;

        return $method;
    }

    private static function executeRuleGenerate(\Closure $status, $err, \Closure $dataIntoMessage = null)
    {
        $info = [
            'data' => self::getData(),
            'first_data' => self::getFirstData(),
            'title' => self::$field_title,
            'titles' => self::$field_titles,
            'inputs' => self::$inputs,
        ];
        $options = self::$params;
        $status = $status($info, $options);
        if (!empty($dataIntoMessage))
            $dataIntoMessage = $dataIntoMessage($info, $options);
        if (is_array($err)) {
            $message_err = $err[0];
            $not_message_err = $err[1];
            self::setMultiResult($status, $message_err, $not_message_err, $dataIntoMessage);
        } else {
            self::setError($err, $dataIntoMessage);
        }
    }

    private static function getData()
    {
        return self::$data['values'];
    }

    private static function getFirstData()
    {
        $data = self::getData();
        return isset($data[0]) ? $data[0] : null;
    }

    private static function setMultiResult($status, $err, $notErr, $dataToStr = array())
    {
        if ($status && self::$isNot) {
            self::setError($notErr, $dataToStr);
        }

        if (!$status && !self::$isNot) {
            self::setError($err, $dataToStr);
        }
    }

    private static function setError($err = "No Message", $dataToStr = array())
    {
        if (self::$isErr) {
            //use custom message if have been set
            $message = self::customizeMessage();
            if ($message === false || is_null($message)) {
                $message = HelperString::replaceData($err, $dataToStr);
            }

            self::$errors[self::$currentKey][] = $message;
            self::setFail(true);
        }

        self::$resultMethod = false;
    }

    private static function customizeMessage()
    {
        if (empty(self::$customMessages)) return false;

        foreach (self::$customMessages as $ck => $cv) {
            $parts = explode(':', $ck);
            $key = isset($parts[0]) ? $parts[0] : null;
            $rule = isset($parts[1]) ? $parts[1] : null;
            $method = self::getMethodName($rule);

            //if is set for specific rule a custom message
            if (self::$currentKey == $key) {
                if (empty($rule) || $method == self::$activeMethod)
                    return $cv;
            }
        }
        return null;
    }

    // emails.*
    // courses.*.seasons.*.lessons.*.title

    private static function isValidCountParams($class, $method, $params)
    {
        $r = new \ReflectionMethod($class, $method);
        $p = $r->getParameters();
        $lengthP = 0;
        $lengthParams = count($params);
        if (count($p) > 0) {
            foreach ($p as $key => $value) {
                if (!$value->isDefaultValueAvailable())
                    $lengthP = $key + 1;
            }
        }

        if ($lengthParams >= $lengthP) return true;
        return false;
    }

    private static function callMethod($class, $method, $params)
    {
        if (self::ignore()) return;
        call_user_func_array(array($class, $method), $params);
    }

    private static function ignore()
    {
        $types = isset(self::$field_types[self::$currentKey]) ? self::$field_types[self::$currentKey] : [];
        $required = self::$data['required'];
        if (!in_array('required', $types) && !$required) return true;
        return false;
    }

    public static function checkOne($value, $rule, $patternArray = null)
    {
        self::$resultMethod = true;

        if (!empty($patternArray)) {
            self::$data = HelperArray::searchArrayByPattern($patternArray, $value);
        } else {
            self::$data['required'] = true;
            self::$data['values'] = [$value];
        }

        $condParts = explode('|', $rule);
        foreach ($condParts as $cond) {
            self::executeRuleMethod($cond);
        }

        return self::$resultMethod;
    }

    public static function isFail()
    {
        return self::$isFail;
    }

    public static function getError()
    {
        $result = [];
        if (!empty(self::$errors)) {
            foreach (self::$errors as $err) {
                $result = array_merge($result, array_values($err));
            }
        }

        return $result;
    }

    /**
     * get first error
     */
    public static function first($key = null)
    {
        $errors = self::getError();
        if (!empty($errors)) {
            if (is_array($errors)) {
                $err = array_shift($errors);
                if (is_array($err))
                    return isset($err[0]) ? $err[0] : null;
                return $err;
            } else {
                return array_shift($errors);
            }
        }

        return array();
    }

    /**
     * get errors
     */
    public static function get($key = null)
    {
        if (isset(self::$errors[$key]))
            return self::$errors[$key];

        return self::$errors;
    }

    public static function __callStatic($method, $arguments)
    {
        self::$resultMethod = true;
        $method = '_' . $method;
        if (count($arguments) < 1) return false;
        $data = $arguments[0];
        array_shift($arguments);
        if (isset(self::$validators[$method])) {
            self::$data['required'] = true;
            self::$data['values'] = [$data];

            $validator = self::$validators[$method];
            self::executeRuleGenerate($validator['status'], $validator['err'], $validator['dataIntoMessage']);
        } else if (method_exists(self::class, $method) && self::isValidCountParams(self::class, $method, $arguments)) {

            self::$data['required'] = true;
            self::$data['values'] = [$data];

            self::callMethod(self::class, $method, $arguments);
            self::$data = null;
            return self::$resultMethod;
        }
    }

    // check count params of a method in class

    public static function generate($name, \Closure $status, $err, \Closure $dataIntoMessage = null)
    {
        self::$validators['_' . $name] = [
            'status' => $status,
            'err' => $err,
            'dataIntoMessage' => $dataIntoMessage,
        ];
    }

    /**
     * @param $lengthParams : this params can mix with math operators
     * example: >=2
     * with getValueFromParams() can extract value : 2
     * with getOperatorFromParams() can extract operator : >=
     */
    private static function _length($lengthParams)
    {
        $data = self::getData();

        $length = self::getValueFromParams($lengthParams);
        $operator = self::getOperatorFromParams($lengthParams);
        if (is_array($data)) {
            foreach ($data as $d) {
                if (is_array($d)) continue;
                if (!self::$is_required && empty($d)) return;

                $dataLen = strlen($d);
                self::compareLength($dataLen, $length, $d, $operator);
            }
        } else {
            if (!self::$is_required && empty($data)) return;
            $dataLen = strlen($data);
            self::compareLength($dataLen, $length, $data, $operator);
        }

    }

    /**
     * this method extract only value without operators
     * example: input : >= 23
     *          output: 23
     */
    private static function getValueFromParams($params)
    {
        return str_replace(self::getOperatorFromParams($params), '', $params);
    }

    /**
     * this method extract only operator without value
     * example: input : >= 23
     *          output: >=
     */
    private static function getOperatorFromParams($params)
    {
        preg_match('/!=|==|<=|<|>=|>/', $params, $out);
        return isset($out[0]) ? $out[0] : '';
    }

    private static function compareLength($dataLen, $length, $data, $operator)
    {
        switch ($operator) {
            case '==':
                {
                    if ($dataLen != $length) self::setError(Lang::get('~validation.err.length_equal'), [self::$field_title, $length]);
                    break;
                }
            case '!=':
                {
                    if ($dataLen == $length) self::setError(Lang::get('~validation.err.not_equal'), [self::$field_title, $length]);
                    break;
                }
            case '>=':
                {
                    if ($dataLen < $length) self::setError(Lang::get('~validation.err.length_gte'), [self::$field_title, $length]);
                    break;
                }
            case '<=':
                {
                    if ($dataLen > $length) self::setError(Lang::get('~validation.err.length_lte'), [self::$field_title, $length]);
                    break;
                }
            case '>':
                {
                    if ($dataLen <= $length) self::setError(Lang::get('~validation.err.length_grater'), [self::$field_title, $length]);
                    break;
                }
            case '<':
                {
                    if ($dataLen >= $length) self::setError(Lang::get('~validation.err.length_lesser'), [self::$field_title, $length]);
                    break;
                }
        }
    }

    /*========================================= Validators ==========================================
     * All Rules Come Here...
     * you can add your custom validators as a method and use it as a rule
     *
     * How to Define own validator ?
     * your method must define as static method and must be start with underline:
     *  -- example -->  public static function _myRule(){ //do staff }
     * also rule support arguments
     */

    private static function _required()
    {
        if (!self::$data['required']) {
            self::setError(Lang::get('~validation.err.required'), self::$field_title);
            return;
        }
        self::$isNot = true;
        self::_empty();
    }

    private static function _empty()
    {
        $value = self::getFirstData();
        if (!is_array($value))
            $value = trim($value);
        $isEmpty = false;
        if (!is_bool($value) && !is_numeric($value) && empty($value))
            $isEmpty = true;

        self::setMultiResult($isEmpty, Lang::get('~validation.err.empty'), Lang::get('~validation.err.not_empty'), self::$field_title);

    }

    private static function _number()
    {
        if (!is_numeric(self::getFirstData())) {
            self::setError(Lang::get('~validation.err.number'), self::$field_title);
        }
    }

    private static function _username()
    {
        if (!preg_match('/^[a-zA-Z]+[_]{0,1}[a-zA-Z0-9]+$/m', self::getFirstData())) {
            self::setError(Lang::get('~validation.err.username'), self::$field_title);
        }
    }

    private static function _extension()
    {
        if (!preg_match('/^[a-zA-Z0-9]+[_]{0,1}[a-zA-Z0-9]+$/m', self::getFirstData())) {
            self::setError(Lang::get('~validation.err.extension'), self::$field_title);
        }
    }

    private static function _match($field, $fieldName2 = null)
    {
        $operator = self::getOperatorFromParams($field);
        $key = str_replace($operator, '', $field);
        $title2 = $key;
        $checkField = $key;
        if (HelperString::firstHas($key, '[') && HelperString::lastHas($key, ']')) {
            $key = str_replace(['[', ']'], '', $key);
            $values = HelperArray::searchArrayByPattern($key, self::$inputs);
            $checkField = isset($values['values'][0]) ? $values['values'][0] : null;
            $title2 = isset(self::$field_titles[$key]) ? self::$field_titles[$key] : $key;
        }

        $fieldName2 = (empty($fieldName2)) ? $title2 : $fieldName2;
        self::compareValue($operator, self::getFirstData(), $checkField, $fieldName2);
    }

    private static function compareValue($operator, $value1, $value2, $fieldName2)
    {
        switch ($operator) {
            case '==':
                {
                    if ($value1 != $value2) self::setError(Lang::get('~validation.err.value_equal'), [self::$field_title, $fieldName2]);
                    break;
                }
            case '!=':
                {
                    if ($value1 == $value2) self::setError(Lang::get('~validation.err.value_not_equal'), [self::$field_title, $fieldName2]);
                    break;
                }
            case '>=':
                {
                    if ($value1 < $value2) self::setError(Lang::get('~validation.err.value_gte'), [self::$field_title, $fieldName2]);
                    break;
                }
            case '<=':
                {
                    if ($value1 > $value2) self::setError(Lang::get('~validation.err.value_lte'), [self::$field_title, $fieldName2]);
                    break;
                }
            case '>':
                {
                    if ($value1 <= $value2) self::setError(Lang::get('~validation.err.value_grater'), [self::$field_title, $fieldName2]);
                    break;
                }
            case '<':
                {
                    if ($value1 >= $value2) self::setError(Lang::get('~validation.err.value_lesser'), [self::$field_title, $fieldName2]);
                    break;
                }
        }
    }

    private static function _email()
    {
        if (filter_var(self::getFirstData(), FILTER_VALIDATE_EMAIL) === false)
            self::setError(Lang::get('~validation.err.email'));
    }

    private static function _url()
    {
        if (filter_var(self::getFirstData(), FILTER_VALIDATE_URL) === false)
            self::setError(Lang::get('~validation.err.url'));
    }

    private static function _signs()
    {
        $isSigns = false;
        if (preg_match('/[,.?\/*&^\\\$%#@()_!|"\~\'><=+}{; ]/', self::getFirstData()))
            $isSigns = true;

        self::setMultiResult($isSigns, Lang::get('~validation.err.sings'), Lang::get('~validation.err.sings'), self::$field_title);
    }

    private static function _name($type)
    {
        $regex = '';
        switch ($type) {
            case 'folder':
                $regex = '/^(?!(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.[^.]*)?$)[^<>:"\\\\|?*\x00-\x1F]*[^<>:"\\\\|?*\x00-\x1F\ .]$/';
                break;
            case 'file':
                $regex = '/^(?!^(PRN|AUX|CLOCK\$|NUL|CON|COM\d|LPT\d|\..*)(\..+)?$)[^\x00-\x1f\\\\?*:\";|><\/]+$/';
                break;
        }

        if (!preg_match($regex, self::getFirstData()))
            self::setError(Lang::get('~validation.err.name'), self::$field_title);

    }

    private static function _int()
    {
        if (filter_var(self::getFirstData(), FILTER_VALIDATE_INT) === false)
            self::setError(Lang::get('~validation.err.int'), self::$field_title);
    }

    private static function _mobile()
    {
        $mobile = self::getFirstData();
        if (strlen($mobile) != 11 || substr($mobile, 0, 2) != '09')
            self::setError(Lang::get('~validation.err.mobile'), self::$field_title);
    }

    private static function _float()
    {
        if (filter_var(self::getFirstData(), FILTER_VALIDATE_FLOAT) === false)
            self::setError(Lang::get('~validation.err.request'), self::$field_title);
    }

    private static function _date($format = 'Y/m/d')
    {
        $isResult = false;
        if (Date::validate($format, self::getFirstData()))
            $isResult = true;

        $fields = (is_array(self::$field_title)) ? self::$field_title : [self::$field_title, $format];
        self::setMultiResult($isResult, Lang::get('~validation.err.date'), Lang::get('~validation.err.not_date'), $fields);
    }

    private static function _jdate()
    {
        $isResult = false;
        $regex = '/\d{2,4}[-|\/]\d{1,2}[-|\/]\d{1,2}[#]/';
        if (preg_match($regex, self::getFirstData() . '#'))
            $isResult = true;

        $fields = (is_array(self::$field_title)) ? self::$field_title : [self::$field_title, 'YY/MM/DD'];
        self::setMultiResult($isResult, Lang::get('~validation.err.date'), Lang::get('~validation.err.not_date'), $fields);
    }

}