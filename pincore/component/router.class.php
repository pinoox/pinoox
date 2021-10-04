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

use pinoox\boot\Loader;
use pinoox\component\app\AppProvider;
use ReflectionClass;
use ReflectionMethod;

class Router
{
    const app_folder = "apps";
    private static $app;
    private static $appUrl = null;
    private static $url;
    private static $isDomain = false;
    private static $isGetController = true;
    private static $isGetMethod = true;
    private static $inParts = array();
    private static $parts;
    private static $isDefaultApp = false;
    private static $inputDataApp = [];
    private static $controllerUrl = null;
    private static $methodUrl = null;
    private static $simpleControllerName;
    private static $tmpParts = [];
    private static $folderController = null;
    private static $folderName = null;

    public static function getFolder()
    {
        return self::$folderName;
    }

    public static function build($key, $app)
    {
        Config::setLinear('~app', $key, $app);
        AppProvider::app($app);
        self::start();
        self::call();
    }

    public static function start()
    {
        self::$url = self::getCurrentUrl();
        self::$url = urldecode(self::$url);
        self::checkDomain();
        self::findAppByUrl();
        self::findPartsByUrl();

    }

    /**get Current url
     *input : test.com/test/test
     *output: test/test
     */
    public static function getCurrentUrl($url = null)
    {
        if (empty($url)) {
            $url = Url::site();
            $url = substr(Url::current(), strlen($url));
        } else {
            $url = substr(Url::request(), strlen($url));
        }

        if (strstr($url, '?')) $url = substr($url, 0, strpos($url, '?'));
        $url = trim($url, '/');
        return $url;
    }

    /***********************
     * App
     */

    private static function checkDomain($url = null)
    {
        $new_url = empty($url) ? self::$url : $url;;
        $app_domain = Config::getLinear('~domain', Url::domain());
        if (empty($app_domain)) {
            $info = self::getByPatternDomain();
            if (!empty($info)) {
                $app_domain = $info['app'];
                $sub = $info['sub'];

                $multiSub = array_reverse(explode('.', $sub));
                $multiSub = implode('/', $multiSub);

                $app_domain = str_replace('{sub}', $sub, $app_domain);
                $app_domain = str_replace('{multi_sub}', $multiSub, $app_domain);
            }
        }

        if (!empty($app_domain)) {
            $app_domain = trim($app_domain, '/');
            self::$isDomain = true;
            $new_url = $app_domain . "/" . $new_url;
            if (empty($url)) {
                self::$url = $new_url;
            }
        }
        return $new_url;
    }

    private static function getByPatternDomain()
    {
        $domain = Config::getLinear('~domain', Url::domain());
        if (is_null($domain)) {
            $domains = Config::get('~domain');

            foreach ($domains as $pattern => $area) {
                if (HelperString::has($pattern, '*')) {

                    list($sub, $domain) = HelperString::divTwoPart($pattern, '*', true, 1);
                    if (HelperString::lastHas(Url::domain(), $domain)) {
                        $currentDomain = HelperString::lastDelete(Url::domain(), $domain);

                        if ($sub == '*') {
                            return ['sub' => $currentDomain, 'app' => $area];
                        }

                        $arrMain = explode('.', $currentDomain);
                        $arrSub = explode('.', $sub);

                        if (self::checkPatternDomain($arrSub, $arrMain)) {
                            return ['sub' => $currentDomain, 'app' => $area];
                        }

                    }

                }
            }
        }
    }

    private static function checkPatternDomain($arrSub, $arrMain)
    {
        $countSub = count($arrSub);
        $countMain = count($arrMain);

        if ($countMain < $countSub) return false;

        $arrSub = array_reverse($arrSub);
        $arrMain = array_reverse($arrMain);

        for ($i = 0; $i < $countSub; $i++) {
            if ($arrSub[$i] == '*') continue;
            if ($i == ($countSub - 1) && $countMain > $countSub) return false;
            $arr = explode(',', $arrSub[$i]);
            if (in_array($arrMain[$i], $arr)) continue;

            return false;
        }

        return true;
    }

    private static function findAppByUrl($url = null)
    {
        $new_url = (empty($url)) ? self::$url : $url;
        $parts = HelperString::explodeDropping('/', $new_url);
        foreach ($parts as $part) {
            $packageName = self::getPackageNameApp($part);
            if (self::existApp($packageName, true) && AppProvider::get('enable') && ((!self::$isDomain) || (self::$isDomain && AppProvider::get('domain')))) {
                self::$appUrl = $part;
                if (empty($url)) {
                    self::setApp($packageName);
                    self::$url = trim(HelperString::firstDelete($new_url, $part), '/');
                    return;
                } else {
                    $app = $packageName;
                    $new_url = trim(HelperString::firstDelete($new_url, $part), '/');
                    return ["app" => $app, "url" => $new_url];
                }

            }
        }

        $default_app = Config::get('~app.*');
        if (self::existApp($default_app, true)) {
            if (empty($url)) {
                self::setApp($default_app);
                self::$isDefaultApp = true;
            } else {
                return ["app" => $default_app, "url" => $new_url];
            }
        }

        if (!empty($url)) return ["app" => "__NO_APP__", "url" => $new_url];
        if (empty(self::getApp())) {
            die("No app available!");
        }
    }

    private static function getPackageNameApp($part)
    {
        if ($part === '*') return null;
        return Config::getLinear('~app', $part);
    }

    public static function existApp($packageName, $isBake = false)
    {
        $app_file = Dir::path('~' . self::app_folder . '/' . $packageName . "/app.php");
        if (file_exists($app_file)) {
            if ($isBake) {
                $app = self::$app;
                self::$app = $packageName;
                self::setAppProvider($packageName);
                self::$app = $app;
            }

            return true;
        }
        return false;
    }

    private static function setAppProvider($packageName = null)
    {
        AppProvider::bake($packageName);
    }

    public static function getApp()
    {
        return self::$app;
    }

    public static function setApp($app)
    {
        $area = AppProvider::get('area');
        if (!empty($app)) {
            self::$app = $app;
        } else {
            self::$app = $area;
        }

        if (Session::isStartOnDatabase())
            Session::app(self::$app);
    }

    /***********************
     * Parts
     */

    private static function findPartsByUrl()
    {
        self::$inParts = self::getAsArray(self::$url);
        self::createParts();
    }

    /**get array url
     *input : test.com/test/test
     *output: ['test','test']
     */
    private static function getAsArray($url, $get = '/')
    {
        return array_values(array_filter(explode($get, $url), function ($value) {
            return $value == 0 || !empty($value);
        }));
    }

    private static function createParts($isUrl = false)
    {
        self::$isGetController = true;
        self::$isGetMethod = true;
        self::replaceConfigRouter();
        self::$tmpParts = [];
        self::findFolderController(self::$inParts);
        $Parts = self::$inParts;

        if (!$isUrl) self::$url = implode('/', $Parts);
        $mainController = AppProvider::get('main-controller');
        $controller = self::generateControllerName($mainController);

        $mainMethod = AppProvider::get('main-method');
        $exceptionMethod = AppProvider::get('exception-method');
        $method = $mainMethod;
        $params = array();

        if (!empty($Parts)) {
            if (isset($Parts[0]) && self::$isGetController) {
                $newController = self::generateControllerName($Parts[0]);
                if ($controller == $newController) {
                    $method = $exceptionMethod;
                } else if (self::isValidController($newController)) {
                    if (!self::checkCommentNoAccess($newController)) {
                        $controller = $newController;
                        unset($Parts[0]);
                        $Parts = array_values($Parts);
                    }
                }
            }

            if (isset($Parts[0])) {
                if (self::$isGetMethod && !HelperString::firstHas($Parts[0], '_')) {
                    $method = $Parts[0];
                    unset($Parts[0]);
                } else {
                    $method = $exceptionMethod;
                }
                $params = $Parts;
            }
        }

        if (self::isValidController($controller)) {
            if (!self::isPublicMethod($controller, $method)) {
                array_unshift($params, $method);
                $method = $exceptionMethod;
            } else if (!self::isValidCountParams($controller, $method, $params)) {
                array_unshift($params, $method);
                $method = $exceptionMethod;
            }

            if (self::checkCommentNoAccess($controller, $method)) {
                if ($method != $mainMethod) array_unshift($params, $method);
                $method = $exceptionMethod;
            }

        }

        $folderController = null;
        if (!empty(self::$folderController))
            $folderController = str_replace('\\', '/', self::$folderController) . '/';
        self::$folderController = null;
        $parts = ['controller' => $folderController . $controller, 'method' => $method, 'params' => array_values($params)];
        $parts['url'] = self::getUrlFromParts($parts);
        if (!$isUrl) self::setParts($parts);
        else return $parts;
    }

    private static function replaceConfigRouter()
    {
        $patterns = AppProvider::get('rewrite');
        $config_router = !empty($patterns) ? $patterns : array();

        if (empty(self::$inParts)) {
            if (isset($config_router['/'])) {
                self::addInParts(HelperString::multiExplode([':', '@', '=>'], $config_router['/']));
            }
            return;
        } else if (count(self::$inParts) > 1) {
            if (isset($config_router['/'])) unset($config_router['/']);
        }

        uksort($config_router, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        if (AppProvider::get('auto-null')) {
            $keys = array_values(array_filter($config_router));
            $newPatterns = array_fill_keys($keys, null);
            $config_router = array_merge($config_router, $newPatterns);

        }
        foreach ($config_router as $key => $value) {
            if (self::checkExist($key, $value)) {
                break;
            }
        }
    }

    private static function addInParts($data)
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                self::$inParts[] = $item;
            }
        } else {
            self::$inParts[] = $data;
        }
    }

    private static function checkExist($search, $value)
    {
        $searchArray = explode('/', $search);
        $search = implode('[+PIN]', $searchArray);
        $string = implode('[+PIN]', self::$inParts);
        if (HelperString::firstHas($string . '[+PIN]', $search . '[+PIN]')) {

            self::actRewriteApp($value, $string, $search);
            return true;
        } else if (self::checkProRewrite($string, $search)) {
            foreach (self::$inputDataApp as $key => $item) {
                $value = str_replace('$' . $key, $item, $value);
            }
            self::actRewriteApp($value, $string, $search);
            return true;
        }
        return false;
    }

    private static function actRewriteApp($value, $string, $search)
    {
        if (!is_null($value)) {
            $value = str_replace(['/', ':', '>'], '[+PIN]', $value);
            $url = substr($string, strlen($search));
            self::$inParts = self::getAsArray($value . $url, '[+PIN]');
        } else {
            $searchArray = explode('/', $search);
            $countSearchArray = count($searchArray);
            if ($countSearchArray > 1) {
                self::$isGetMethod = false;
            } else if ($countSearchArray == 1) {
                self::$isGetController = false;
            }
        }
    }

    private static function checkProRewrite($main, &$rewrite)
    {
        $main = explode('[+PIN]', $main);
        $rewrite = explode('[+PIN]', $rewrite);
        $countRewrite = 0;
        foreach ($rewrite as $_key => $_value) {
            if (HelperString::firstHas($_value, "?")) {
                $rewrite[$_key] = HelperString::firstDelete($_value, "?");
            } else {
                $countRewrite++;
            }
        }
        if (count($main) < $countRewrite) return false;
        $isValid = true;
        $inputDataApp = [];
        foreach ($rewrite as $i => $r) {
            if (HelperString::firstHas($r, "$")) {
                $isTempValid = true;
                //check rewrite filter
                $keys = explode('=', $r);
                $key = $keys[0];
                $default = isset($keys[1]) ? $keys[1] : null;
                $value = !empty($main[$i]) ? $main[$i] : $default;
                $filters = AppProvider::get('rewrite-filter');
                if (isset($filters[$key])) {
                    $filter = $filters[$key];
                    if (is_array($filter)) {
                        if (!in_array($value, $filter)) {
                            $isValid = false;
                            $isTempValid = false;
                        }
                    } else if (is_callable($filter)) {
                        if (!$filter($value)) {
                            $isValid = false;
                            $isTempValid = false;
                        }
                    }
                }
                $key = HelperString::firstDelete($key, '$');

                // set input global
                $inputDataApp[$key] = $isTempValid ? $value : $default;
                $rewrite[$i] = $value;
                continue;
            } else if ($r == $main[$i]) {
                continue;
            } else {
                return false;
            }
        }

        $rewrite = implode('[+PIN]', $rewrite);
        self::$inputDataApp = $inputDataApp;
        return $isValid;
    }

    private static function findFolderController($parts, $controller = null)
    {
        if (empty($parts)) {
            self::$folderController = null;
            return;
        }
        $folder = implode('\\', $parts);
        if (!self::existFolderController($folder)) {
            $controller = array_pop($parts);
            array_unshift(self::$tmpParts, $controller);
            self::findFolderController($parts, $controller);

            return;
        }
        self::$folderController = self::$folderName = $folder;
        if (!empty(self::$folderController)) {
            $folders = HelperString::multiExplode(['/', '\\'], self::$folderController);
            self::$folderController = implode('\\', array_filter($folders));
        }
        if (!empty($controller) && self::checkAccessToController($controller)) {
            self::setInParts();
            return;
        }

        if (self::checkAccessToController()) {
            self::setInParts();
            return;
        }

        $controller = array_pop($parts);
        array_unshift(self::$tmpParts, $controller);
        self::findFolderController($parts, $controller);
    }

    private static function existFolderController($folder)
    {
        return is_dir(Dir::path('controller/' . $folder));
    }

    private static function checkAccessToController($controller = null)
    {
        $controller = !empty($controller) ? $controller : AppProvider::get('main-controller');
        $controller = self::generateControllerName($controller);
        return self::isValidController($controller) && !self::checkCommentNoAccess($controller);
    }

    private static function generateControllerName($name)
    {
        self::$simpleControllerName = $name;
        return ucfirst($name) . 'Controller';
    }

    /***********************
     * Helper Class
     */

    private static function isValidController($controller)
    {
        $controller = (is_object($controller)) ? $controller : self::generateControllerClass($controller);
        if (class_exists($controller)) {
            return true;
        }
        return false;
    }

    private static function generateControllerClass($controllerName)
    {
        $folderController = !empty(self::$folderController) ? self::$folderController . '\\' : null;
        $namespace = "pinoox\\app\\" . self::getApp() . "\\controller\\" . $folderController;
        $controllerName = str_replace('/', '\\', $controllerName);
        return $namespace . $controllerName;
    }

    private static function checkCommentNoAccess($controller, $method = null)
    {
        $controllerClass = self::generateControllerClass($controller);

        if (empty($method)) {
            $rc = new ReflectionClass($controllerClass);
            $string = $rc->getDocComment();
            if (self::checkNoAccessComment($string)) {
                $mainController = AppProvider::get('main-controller');
                if ($controller == self::generateControllerName($mainController)) exit("No Access!");
                return true;
            }
        } else {
            $rc = new ReflectionClass($controllerClass);
            $string = $rc->getMethod($method)->getDocComment();
            if (self::checkNoAccessComment($string)) {
                $exceptionMethod = AppProvider::get('exception-method');
                if ($method == $exceptionMethod) exit("No Access!");
                return true;
            }
        }
        return false;
    }

    private static function checkNoAccessComment($string)
    {
        $pattern = "[no-access]";
        if (HelperString::has($string, $pattern)) {
            return true;
        }

        $pattern = "[access-user]";
        if (HelperString::has($string, $pattern)) {
            if (!User::isLoggedIn()) {
                return true;
            }
        }
    }

    private static function setInParts()
    {
        self::$inParts = self::$tmpParts;
        self::$tmpParts = [];
    }

    private static function isPublicMethod($class, $method)
    {
        $class = (is_object($class)) ? $class : self::generateControllerClass($class);
        $status = false;
        if (method_exists($class, $method)) {
            $reflection = new ReflectionMethod($class, $method);
            $status = $reflection->isPublic();
        }

        return $status;
    }

    private static function isValidCountParams($class, $method, $params)
    {
        $class = (is_object($class)) ? $class : self::generateControllerClass($class);
        $r = new ReflectionMethod($class, $method);
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

    public static function getUrlFromParts($parts = null)
    {
        $parts = (empty($parts)) ? self::getParts() : $parts;
        $controller = $parts['controller'];
        $method = $parts['method'];
        $params = $parts['params'];

        $getArray = array();
        $controller = HelperString::lastDelete($controller, 'Controller');
        $arrController = explode('/', $controller);
        $controller = array_pop($arrController);
        $controller = lcfirst($controller);
        $arrController[] = $controller;
        $controller = implode('/', $arrController);
        if (!empty($controller)) {
            $getArray[] = $controller;
        }
        if (!empty($method)) {
            $getArray[] = $method;
        }
        if (!empty($params)) {
            foreach ($params as $param) {
                $getArray[] = $param;
            }
        }

        return (!empty($getArray)) ? join('/', $getArray) : '';
    }

    public static function getParts($type = null)
    {
        if (!empty($type)) {
            return self::$parts[$type];
        } else {
            return self::$parts;
        }
    }

    public static function setParts($parts, $type = null)
    {
        if (is_array($parts) && empty($type)) {
            self::$parts = $parts;
        } else if (!is_array($parts) && $type == 'params') {
            self::$parts[$type][] = $parts;
        } else {
            self::$parts[$type] = $parts;
        }
    }

    public static function call($parts = null, $app = null)
    {
        $parts = (empty($parts)) ? self::getParts() : $parts;
        if (!empty($app)) {
            self::setApp($app);
        }

        $controller = self::generateControllerClass($parts['controller']);

        if (self::isValidController($parts['controller'])) {
            self::startup();
            $controllerInstance = new  $controller();
            if (self::isPublicMethod($controllerInstance, $parts['method']) && self::isValidCountParams($controllerInstance, $parts['method'], $parts['params'])) {
                call_user_func_array(array($controllerInstance, $parts['method']), $parts['params']);
            } else {
                exit('no Valid Method in Controller!');
            }
        } else {
            exit('Not Exists ' . $parts['controller'] . '!');
        }

        if (!empty($app)) {
            self::setApp(AppProvider::get('area'));
        }
    }

    private static function startup()
    {
        self::loadLoader();
        self::loadSession();
        self::loadToken();
        self::loadUser();
        self::loadLang();
        self::loadInputDataInGlobal();
        self::loadServices();
        AppProvider::call('startup');
    }

    private static function loadLoader()
    {
        $loaders = AppProvider::get('loader');
        foreach ($loaders as $classname => $path) {
            if (HelperString::firstHas($classname, '@')) {
                Loader::loadPath($classname, $path);
                continue;
            }
            Config::setLinear('~loader', $classname, $path);
        }
    }

    private static function loadSession()
    {
        $app = AppProvider::get('session');
        if (!empty($app))
            Session::app($app);
    }

    private static function loadToken()
    {
        $app = AppProvider::get('token');
        if (!empty($app))
            Token::app($app);
    }

    private static function loadUser()
    {
        $app = AppProvider::get('user');
        if (!empty($app))
            User::app($app);

        $type = AppProvider::get('user-type');
        if (!empty($type))
            User::type($type);
    }

    private static function loadLang()
    {
        $lang = AppProvider::get('lang');
        $lang = !empty($lang) ? $lang : PINOOX_DEFAULT_LANG;
        Lang::change($lang);
    }

    private static function loadInputDataInGlobal()
    {
        $inputData = self::getInputData();
        if (!AppProvider::get('global-data')) return;
        $prefix = AppProvider::get('prefix-data');
        foreach ($inputData as $key => $value) {
            $GLOBALS[$prefix . $key] = $value;
        }
    }

    public static function getInputData($type = null)
    {
        if (!empty($type)) {
            return self::$inputDataApp[$type];
        } else {
            return self::$inputDataApp;
        }
    }

    private static function loadServices()
    {
        $services = AppProvider::get('service');
        if (!is_array($services)) return;
        foreach ($services as $service) {
            Service::run($service);
        }
    }

    public static function isAppDefault()
    {
        return self::$isDefaultApp;
    }

    public static function getControllerUrl($isProtocol = false)
    {
        if (empty(self::$controllerUrl)) self::convertPartToUrl($isProtocol);
        return self::$controllerUrl;
    }

    private static function convertPartToUrl($isProtocol = false, $isMethod = false)
    {
        $url = Url::request();
        if (strstr($url, '?')) $url = substr($url, 0, strpos($url, '?'));
        $url = trim($url, '/');
        $url = implode('/', self::getAsArray($url));
        $method = self::method();
        $deleteUrl = '/';
        if (!$isMethod && $method != AppProvider::get('main-method') && $method != AppProvider::get('exception-method'))
            $deleteUrl .= $method;


        $params = implode("/", self::params());
        $deleteUrl .= (!empty($params)) ? '/' . $params : '';
        if (strlen($deleteUrl) > 1) {
            $url = HelperString::lastDelete($url, $deleteUrl);
        }

        $url = '/' . $url . '/';
        $url = urldecode($url);
        if ($isProtocol) $url = self::setProtocolForUrl($url);
        if ($isMethod) self::$methodUrl = $url;
        else self::$controllerUrl = $url;
    }

    public static function method()
    {
        return self::getParts('method');
    }

    public static function params()
    {
        return self::getParts('params');
    }

    private static function setProtocolForUrl($url)
    {
        return Url::fullDomain() . $url;
    }

    public static function getMethodUrl($isProtocol = false)
    {
        if (empty(self::$methodUrl)) self::convertPartToUrl($isProtocol, true);

        return self::$methodUrl;
    }

    public static function convertUrl($url)
    {
        self::$inParts = self::getAsArray($url);
        $parts = self::createParts(true);
        $url = self::getUrlFromParts($parts);
        return $url;
    }

    public static function setInputData($key, $value)
    {
        self::$inputDataApp[$key] = $value;
    }

    public static function selfUrl()
    {
        $url = self::getCurrentUrl();
        $url = HelperString::firstDelete($url, self::$appUrl);
        $url = HelperString::firstDelete($url, '/');
        return $url;
    }

    // check method class is public

    public static function checkFirstUrl($value)
    {
        return (HelperString::firstHas(self::url(), $value)) ? true : false;
    }

    // check count params of a method in class

    public static function url()
    {
        return self::getParts('url');
    }

    public static function controller()
    {
        return self::getParts('controller');
    }

    public static function getAppUrl()
    {
        return self::$appUrl;
    }

    public static function controllerMethodUrl()
    {
        return self::simpleController() . '/' . self::method();
    }

    public static function simpleController()
    {
        return self::$simpleControllerName;
    }

}