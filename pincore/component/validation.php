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

use Closure;
use ReflectionClass;
use ReflectionMethod;

/**
 * Validation Help you to check validity of input data in a simple way
 *
 * Class Validation
 * @package pinoox\component
 */
class Validation
{

    /**
     * An instance of Validation Class
     *
     * @var Validation
     */
    private static $obj;

    /**
     * Given inputs data from request component
     *
     * @var array
     */
    private static $inputs;

    /**
     * Define conditions and rules
     *
     * @var array
     */
    private static $rules;

    /**
     * By default all rules have some messages that you can set your own message for each rule
     *
     * @var array
     */
    private static $customMessages;

    /**
     * List of validators
     *
     * @var
     */
    private static $listMethodValidators;

    /**
     * Error messages
     *
     * @var array
     */
    private static $errors = [];

    /**
     * Check is validation has error
     *
     * @var boolean
     */
    private static $isFail;

    /**
     * Given data array for validating
     *
     * @var array
     */
    private static $data;

    /**
     * Store key index of an array
     *
     * @var string
     */
    private static $field_title;

    /**
     * Store current key of data
     *
     * @var string
     */
    private static $currentKey;

    /**
     * Check is disclaimer
     *
     * @var bool
     */
    private static $isNot = false;

    /**
     * Determine which rule is running
     *
     * @var string
     */
    private static $activeMethod;

    /**
     * Checking for an error
     *
     * @var bool
     */
    private static $isErr = false;

    /**
     * @var bool
     */
    private static $resultMethod = true;

    /**
     * List of rule's params
     *
     * @var array
     */
    private static $params = [];

    /**
     * Validators are methods that use as rule
     *
     * @var array
     */
    private static $validators = [];

    /**
     * titles of data give for checking
     *
     * @var array
     */
    private static $field_titles = [];

    /**
     * @var array
     */
    private static $field_types = [];

    /**
     * Check is require
     *
     * @var bool
     */
    private static $is_required = false;

    /**
     * Check and validate
     *
     * @param array $inputs pass an array from Request component
     * @param array $rules define array of rules for checking
     * @param null $messages custom message
     * @return Validation
     */
    public static function check($inputs, $rules, $messages = null)
    {
        self::$isErr = true;
        self::$inputs = $inputs;
        self::$rules = $rules;
        self::$customMessages = $messages;
        self::$errors = [];

        self::init();
        self::validateInputsByRules();

        return self::$obj;
    }

    /**
     * Initialize Validation component
     *
     */
    private static function init()
    {
        if (empty(self::$obj))
            self::$obj = new Validation();
        self::getDefinedValidators();
        self::setFail(false);
    }

    /**
     * Get Validator methods (rules)
     *
     */
    private static function getDefinedValidators()
    {
        if (!empty(self::$listMethodValidators)) return;

        $class = new ReflectionClass(self::class);
        $methods = $class->getMethods(
            ReflectionMethod::IS_PUBLIC |
            ReflectionMethod::IS_PROTECTED |
            ReflectionMethod::IS_PRIVATE
        );
        foreach ($methods as $method) {
            $mName = $method->name;
            if (substr($mName, 0, 1) == '_') {
                self::$listMethodValidators[] = $mName;
            }
        }
    }

    /**
     * Set fail status in validate
     *
     * @param bool $status
     */
    private static function setFail($status = true)
    {
        self::$isFail = $status;
    }

    /**
     * Validate inputs by validators (rules)
     */
    private static function validateInputsByRules()
    {
        foreach (self::$rules as $key => $conditions) {
            self::$currentKey = $key;
            if (is_array($conditions)) {
                $partsCond = $conditions[0];
                self::$field_title = isset($conditions[1]) ? $conditions[1] : '';
            } else {
                $partsCond = $conditions;
            }

            self::$field_titles[$key] = self::$field_title;

            self::$data = HelperArray::detachByPattern($key, self::$inputs);

            $condParts = is_array($partsCond) ? $partsCond : explode('|', $partsCond);
            self::$field_types[$key] = $condParts;
            self::$is_required = in_array('required', $condParts);
            foreach ($condParts as $cond) {
                self::executeRuleMethod($cond);
            }
        }
    }

    /**
     * Execute rule
     *
     * @param $ruleName
     * @throws \ReflectionException
     */
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

    /**
     * Get method name by pass rule
     *
     * @param $ruleName
     * @return string
     */
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

    /**
     * Execute rule
     *
     * @param Closure $status
     * @param $err
     * @param Closure|null $dataIntoMessage
     */
    private static function executeRuleGenerate(Closure $status, $err, Closure $dataIntoMessage = null)
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
            if (!$status)
                self::setError($err, $dataIntoMessage);
        }
    }

    /**
     * Get data
     *
     * @return mixed
     */
    private static function getData()
    {
        return self::$data['values'];
    }

    /**
     * Get first data
     *
     * @return null
     */
    private static function getFirstData()
    {
        $data = self::getData();
        return isset($data[0]) ? $data[0] : null;
    }

    /**
     * Set multiple result
     *
     * @param boolean $status fail or success status
     * @param string $err error message
     * @param boolean $notErr success message
     * @param array $dataToStr
     */
    private static function setMultiResult($status, $err, $notErr, $dataToStr = array())
    {
        if ($status && self::$isNot) {
            self::setError($notErr, $dataToStr);
        }

        if (!$status && !self::$isNot) {
            self::setError($err, $dataToStr);
        }
    }

    /**
     * Set error
     *
     * @param string $err
     * @param array $dataToStr
     */
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

    /**
     * Customize message
     *
     * @return bool|mixed|null
     */
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

    /**
     * Check params of rules is valid or not
     *
     * @param object $class
     * @param string $method
     * @param string $params
     * @return bool
     * @throws \ReflectionException
     */
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

    /**
     * Call static methods
     *
     * @param object $class
     * @param string $method
     * @param string $params
     */
    private static function callMethod($class, $method, $params)
    {
        if (self::ignore()) return;
        call_user_func_array(array($class, $method), $params);
    }

    /**
     * If don't use of required rule should ignore validate data
     *
     * @return bool
     */
    private static function ignore()
    {
        $types = isset(self::$field_types[self::$currentKey]) ? self::$field_types[self::$currentKey] : [];
        $required = self::$data['required'];
        if (!in_array('required', $types) && !$required) return true;
        return false;
    }

    /**
     * Validate a value
     *
     * @param string $value
     * @param string|array $rule
     * @param null $patternArray
     * @return bool
     * @throws \ReflectionException
     */
    public static function checkOne($value, $rule, $patternArray = null)
    {
        self::$resultMethod = true;

        if (!empty($patternArray)) {
            self::$data = HelperArray::detachByPattern($patternArray, $value);
        } else {
            self::$data['required'] = true;
            self::$data['values'] = [$value];
        }

        $condParts = is_array($rule) ? $rule : explode('|', $rule);
        foreach ($condParts as $cond) {
            self::executeRuleMethod($cond);
        }

        return self::$resultMethod;
    }

    /**
     * Check has error
     *
     * @return bool
     */
    public static function isFail()
    {
        return self::$isFail;
    }

    /**
     * Get first error
     *
     * @return array|mixed|null
     */
    public static function first()
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
     * Get errors
     *
     * @return array
     */
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
     * Get errors
     *
     * @param string $key retrieve specific error
     * @return array
     */
    public static function get($key = null)
    {
        if (!is_null($key) && isset(self::$errors[$key]))
            return self::$errors[$key];

        return self::$errors;
    }

    /**
     * Get first errors by fields
     *
     * @return array
     */
    public static function getFieldError()
    {
        $result = [];
        foreach (self::$errors as $key => $errs) {
            if (isset($errs[0])) {
                $result[$key] = $errs[0];
            }
        }

        return $result;
    }

    /**
     * Call static methods
     *
     * @param $method
     * @param $arguments
     * @return bool
     * @throws \ReflectionException
     */
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

    /**
     * Generate
     *
     * @param $name
     * @param Closure $status
     * @param $err
     * @param Closure|null $dataIntoMessage
     */
    public static function generate($name, Closure $status, $err, Closure $dataIntoMessage = null)
    {
        self::$validators['_' . $name] = [
            'status' => $status,
            'err' => $err,
            'dataIntoMessage' => $dataIntoMessage,
        ];
    }

    /**
     * Length of value
     *
     * example: ">=2"
     * with getValueFromParams() can extract value : 2
     * with getOperatorFromParams() can extract operator : >=
     *
     * @param int $lengthParams this param can mix with math operators
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
     * This method extract only value without operators
     * example: input : >= 23
     *          output: 23
     * @param $params
     * @return mixed
     */
    private static function getValueFromParams($params)
    {
        return str_replace(self::getOperatorFromParams($params), '', $params);
    }

    /**
     * This method extract only operator without value
     *
     * example: input : >= 23
     *          output: >=
     * @param $params
     * @return string
     */
    private static function getOperatorFromParams($params)
    {
        preg_match('/!=|==|<=|<|>=|>/', $params, $out);
        return isset($out[0]) ? $out[0] : '';
    }

    /**
     * Compare length
     * @param $dataLen
     * @param $length
     * @param $data
     * @param $operator
     */
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
     * You can add your custom validators as a method and use it as a rule
     *
     * How to Define own validator ?
     * Your method must define as static method and must be start with underline:
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
            $values = HelperArray::detachByPattern($key, self::$inputs);
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
        if (Date::validate(self::getFirstData(), $format))
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