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

use pinoox\component\app\AppProvider;

class Template
{

    public static $path;
    public static $folder;
    public static $pathTheme = null;
    private static $data = array();
    private static $dataJs = array();
    private static $info = null;
    private $isFooterMapAssets = true;
    private $isHeaderMapAssets = true;
    private $header = false;
    private $footer = false;
    private $isShowHeaders = true;
    private $isShowFooter = true;
    private $isShowViews = true;
    private $viewsList = array();
    private $headerViewsList = array();
    private $footerViewsList = array();
    private $offHeaderViewsList = array();
    private $offFooterViewsList = array();
    private $offViewsList = array();
    private $linkBaseMapAssets = '';
    private $isMinify = false;
    const DS = DIRECTORY_SEPARATOR;

    /*
     * Class Template
     *
     * show all
     * ^^^^^^^^^^^^^^^^^
     * $template = new Template('path\theme,'default');
     * $template->setBaseUrlMapAssets('domain/theme');
     * $template->setPageTitle('site');
     * $template->setUser(['id'=>1,'username'=>'ali']);
     * $template->setBaseLink('domain');
     * $template->set(key,value);
     * $template->header();
     * $template->footer();
     * $template->view('page1');
     * $template->offHeader();
     * $template->addToHeader('menu');
     * $template->removeFromHeader('menu');
     * $template->offFooter();
     * $template->addToFooter('copyright');
     * $template->removeFromFooter('copyright');
     */
    public function __construct($path = null, $folder = null)
    {
        ob_start(array($this, 'minifyOutput'));
        self::$data['_title'] = '';
        self::$data['_user'] = array();
        $this->headerViewsList[] = 'header';
        $this->footerViewsList[] = 'footer';
        self::$folder = AppProvider::get('theme');
        self::$pathTheme = AppProvider::get('path-theme');
        self::$pathTheme = Dir::path(self::$pathTheme);
        self::$pathTheme = HelperString::lastDelete(self::$pathTheme, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($folder != null)
            self::$folder = $folder;

        if ($path != null) {
            if (!$this->characterLastHas($path, DIRECTORY_SEPARATOR)) {
                $path .= DIRECTORY_SEPARATOR;
            }
            self::$pathTheme = $path;
        }

        $path = HelperString::lastDelete(self::$pathTheme, DIRECTORY_SEPARATOR);
        Dir::setTheme(self::$folder, $path);
        Url::setTheme(self::$folder, $path);

        self::$path = self::$pathTheme . self::$folder . DIRECTORY_SEPARATOR;
        self::$data['_url'] = Url::link('~' . self::$path);
        self::setBaseUrlMapAssets(self::$data['_url']);

        self::$info = self::loadInfoInTheme();
    }

    private function characterLastHas($string, $character)
    {
        if (substr($string, -1) == $character) {
            return true;
        }
        return false;
    }

    /**
     * this method return information of template from info.json in template folder
     * @link https://pinoox.com/documnet/pintemplate/methods/loadInfoInTheme
     * @param String $folder : name of folder of template
     * @param String $dir : path of folder of template
     * @return array|null : list of template info or return null on don't exist
     */
    public static function loadInfoInTheme($folder = null, $dir = null)
    {
        if (!empty($dir)) {
            $path = $dir . $folder . DIRECTORY_SEPARATOR;
        } else {
            $path = (!empty($folder)) ? self::$pathTheme . $folder . DIRECTORY_SEPARATOR : self::$path;

        }
        $path_options = $path . 'info.json';
        if (is_file($path_options)) {
            $json = file_get_contents($path_options);
            return HelperString::decodeJson($json);
        }
        return null;
    }

    /**
     * this method use in the template for get url of current template render
     * @link https://pinoox.com/documnet/pintemplate/methods/getThemeUrl
     * @return string of template url
     */
    public static function getThemeUrl()
    {
        return (isset(self::$data['_url'])) ? self::$data['_url'] : '';
    }

    public static function getConfig()
    {
        return (isset(self::$data['_config'])) ? self::$data['_config'] : array();
    }

    /**
     * this method show lasted folder of current template
     * @link https://pinoox.com/documnet/pintemplate/methods/getThemeFolder
     * @return string that name of lasted folder of current template
     */
    public static function getThemeFolder()
    {
        return self::$folder;
    }

    public static function includeView($path, $data = array(), $isAllLoad = false)
    {
        $files = self::actInclude($path, $isAllLoad);

        if (empty($files)) return;

        if (empty($data))
            $data = self::$data;
        else
            $data = array_merge(self::$data, $data);
        extract($data);

        foreach ($files as $file) {
            include $file;
        }
    }

    private static function actInclude($path, $isAllLoad = false)
    {
        $files = [];
        if (empty($path)) return $files;

        if (is_array($path)) {
            foreach ($path as $getPath) {
                if ($file = self::getPathView($getPath . '.php')) {
                    $files[] = $file;
                    if (!$isAllLoad) break;
                }
            }
        } else {
            if ($file = self::getPathView($path . '.php')) {
                $files[] = $file;
            }
        }
        return $files;
    }

    public function getThemePath()
    {
        return self::$path;
    }

    public function setBaseUrlMapAssets($link)
    {
        $this->linkBaseMapAssets = $link;
    }

    public function offAllHeaders()
    {
        $this->isShowHeaders = false;
    }

    public function offHeader()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                $this->offHeaderViewsList[] = self::fixPathSeparator($arg);
            }
        }
    }

    private static function fixPathSeparator($section)
    {
        $view = str_replace(['/', '\\', '>'], DIRECTORY_SEPARATOR, $section);
        return $view;
    }

    public function offHeaderMapAssets()
    {
        $this->isHeaderMapAssets = false;
    }

    public function addToAfterHeader()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                $this->headerViewsList[] = self::fixPathSeparator($arg);
            }
        }
    }

    public function addToBeforeHeader()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                array_unshift($this->headerViewsList, $arg);
            }
        }
    }

    public function removeFromHeader()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();
            foreach ($args as $arg) {
                $arg = self::fixPathSeparator($arg);
                foreach ($this->headerViewsList as $key => $headerView) {
                    if ($headerView == $arg) {
                        unset($this->headerViewsList[$key]);
                        break;
                    }
                }
            }
        }
    }

    public function show($section)
    {
        $this->header();
        if (is_array($section)) {
            foreach ($section as $s) {
                $this->view($s);
            }
        } else {
            $this->view($section);
        }
        $this->footer();
    }

    public function header()
    {
        $this->header = true;
    }

    public function view($section)
    {
        $this->viewsList[] = self::fixPathSeparator($section);
    }

    public function footer()
    {
        $this->footer = true;
    }

    public function offAllViews()
    {
        $this->isShowViews = false;
    }

    public function offView()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                $this->offViewsList[] = self::fixPathSeparator($arg);
            }
        }
    }

    public function addToAfterView()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                $this->viewsList[] = $arg;
            }
        }
    }

    public function addToBeforeView()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                array_unshift($this->viewsList, $arg);
            }
        }
    }

    public function removeFromView()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();
            foreach ($args as $arg) {
                $arg = self::fixPathSeparator($arg);
                foreach ($this->viewsList as $key => $view) {
                    if ($view == $arg) {
                        unset($this->viewsList[$key]);
                        break;
                    }
                }
            }
        }
    }

    public function offAllFooters()
    {
        $this->isShowFooter = false;
    }

    public function offFooter()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                $this->offFooterViewsList[] = self::fixPathSeparator($arg);
            }
        }
    }


    public function offFooterMapAssets()
    {
        $this->isFooterMapAssets = false;
    }


    public function addToAfterFooter()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                $this->footerViewsList[] = self::fixPathSeparator($arg);
            }
        }
    }

    public function addToBeforeFooter()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $arg) {
                array_unshift($this->footerViewsList, self::fixPathSeparator($arg));
            }
        }
    }

    public function removeFromFooter()
    {
        if (func_num_args() > 0) {
            $args = func_get_args();
            foreach ($args as $arg) {
                $arg = self::fixPathSeparator($arg);
                foreach ($this->footerViewsList as $key => $footerView) {
                    if ($footerView == $arg) {
                        unset($this->footerViewsList[$key]);
                        break;
                    }
                }
            }
        }
    }

    /**
     * this method get page title values to set in template and render it when template is compiling
     * @link https://pinoox.com/documnet/pintemplate/methods/setPageTitle
     * @param string|object|int|boolean|null $title : value that return when <_title> call from front
     */
    public function setPageTitle($title)
    {
        self::set('_title', $title);
    }

    /**
     * this method get variable to set in template and render it when template is compiling
     * @link https://pinoox.com/documnet/pintemplate/methods/set
     * @param string $varibale : name of variable that call it from front
     * @param string|object|int|boolean|null $value : value that return when variable call from front
     * @param array : 2D array that index of array is variable that call from front and value of array return on call variable in front
     * @example : (new template)->set('username' , 'admin');
     * @example : (new template)->set( array( 'username' => 'admin' ) );
     * @example : (new template)->set( 'username' => $object ) );
     * @example : (new template)->set( 'user' => array( 'username' => 'admin' , 'userAvatar' => 'avatar.png' ) ) );
     */
    public function set()
    {
        $args = func_get_args();
        $num = func_num_args();
        if ($num == 2) {
            self::$data[$args[0]] = $args[1];
        } else {
            if (is_array($args[0])) {
                foreach ($args[0] as $k => $v) {
                    self::$data[$k] = $v;
                }
            }
        }
    }

    /**
     * this method get user information to set in template and render it when template is compiling
     * @link https://pinoox.com/documnet/pintemplate/methods/setUser
     * @param string|object|int|boolean|null $info : value that return when <_user> call from front
     */
    public function setUser($info)
    {
        self::set('_user', $info);
    }

    /**
     * this method get base url to set in template and render it when template is compiling
     * @link https://pinoox.com/documnet/pintemplate/methods/setBaseUrl
     * @param string|object|int|boolean|null $link : value that return when <_baseUrl> call from front.
     */
    public function setBaseUrl($link)
    {
        self::set('_baseUrl', $link);
    }

    /**
     * this method get url to set in template and render it when template is compiling
     * @link https://pinoox.com/documnet/pintemplate/methods/setUrl
     * @param string|object|int|boolean|null $link : value that return when <_url> call from front
     */
    public function setUrl($link)
    {
        if (!$this->characterLastHas($link, '/')) {
            $link .= '/';
        }
        $link = $link . self::$folder . '/';
        self::set('_url', $link);
        if (empty($this->linkBaseMapAssets))
            $this->linkBaseMapAssets = $link;
    }

    /**
     * this method get config values to set in template and render it when template is compiling
     * @link https://pinoox.com/documnet/pintemplate/methods/setConfig
     * @param string|object|int|boolean|null $configs : value that return when <_config> call from front
     */
    public function setConfig($configs)
    {
        self::set('_config', $configs);
    }

    public function __get($name)
    {
        return isset(self::$data[$name]) ? self::$data[$name] : null;
    }

    public function __set($name, $value)
    {
        self::$data[$name] = $value;
    }

    /**
     * this method get variable to unset that
     * @link https://pinoox.com/documnet/pintemplate/methods/unset
     * @param string $key : name of variable that call it from front
     */
    public function _unset($key)
    {
        if (isset(self::$data[$key]))
            unset(self::$data[$key]);
    }

    /**
     * this method get variable to check set before and return value of that or not set yet
     * @link https://pinoox.com/documnet/pintemplate/methods/unset
     * @param string $key : name of variable that needed value
     * @return string|object|int|boolean|null : value of variable that needed
     */
    public function get($key)
    {
        return (isset(self::$data[$key])) ? self::$data[$key] : null;
    }

    public function appendToJS($key, $value)
    {
        self::$dataJs[$key] = $value;
    }

    public function buildJs($data = null)
    {
        if (!empty($data)) self::$dataJs = $data;
        if (self::isJs()) exit;
    }

    public static function isJs()
    {
        $isJs = Request::getOne('pin_javascript_cache', null);
        return (!is_null($isJs));
    }

    /**
     * this method get variable to check set before and return value of that or not set yet
     * @link https://pinoox.com/documnet/pintemplate/methods/getData
     * @return array : list of all data that set before from backend
     */
    public function getData()
    {
        return self::$data;
    }

    public function getDataJs()
    {
        return self::$dataJs;
    }

    public function __destruct()
    {
        $this->renderJs();
        $this->render();
    }

    private function renderJs()
    {
        if (!self::isJs() || empty(self::$dataJs)) return;
        self::loadJs();
        ob_end_flush();
        exit;
    }

    private static function loadJs()
    {
        $data = (is_array(self::$dataJs)) ? HelperArray::convertToObjectJavascript(self::$dataJs) : self::$dataJs;
        HelperHeader::contentType('application/javascript', 'UTF-8');
        echo 'const cPINOOX = ' . $data;
    }

    private function render()
    {
        // Map Load
        $this->_mapAssets();

        if (!empty(self::$data))
            extract(self::$data);

        //Functions Load
        if ($file = self::getPathView('functions.php')) {
            include_once $file;
        }

        //Header Load
        if ($this->header) {
            if ($this->isShowHeaders && !empty($this->headerViewsList)) {
                foreach ($this->headerViewsList as $section) {

                    if (in_array($section, $this->offHeaderViewsList)) continue;

                    if ($file = self::getPathView($section . '.php')) {
                        include_once $file;
                    }
                }
            }
        }

        //Contents Load
        if ($this->isShowViews && !empty($this->viewsList)) {
            foreach ($this->viewsList as $section) {
                if (in_array($section, $this->offViewsList)) continue;
                if ($file = self::getPathView($section)) {
                    include_once $file;
                }
            }
        }

        //Footer Load
        if ($this->footer) {
            if ($this->isShowFooter && !empty($this->footerViewsList)) {
                foreach ($this->footerViewsList as $section) {
                    if (in_array($section, $this->offFooterViewsList)) continue;
                    if ($file = self::getPathView($section . '.php')) {
                        include_once $file;
                    }
                }
            }
        };
        ob_end_flush();
    }

    private function _mapAssets()
    {
        if (file_exists($file = self::$path . 'mapAssets.php')) {
            include_once $file;

            if (isset($map)) {
                $plugin_map = $this->getPluginMap($map);
                $this->setMapData($plugin_map);
                $this->setMapData($map);
            }

        }

        $this->addMapAssetsForJavascript();
    }

    private function getPluginMap(&$map)
    {
        $plugin_map = null;
        $call_map = null;
        if (isset($map['@plugin'])) {
            $plugin_map = $map['@plugin'];
            unset($map['@plugin']);

        }
        if (isset($map['@call'])) {
            $call_map = $map['@call'];
            unset($map['@call']);
        }
        if (empty($plugin_map) || empty($call_map)) return [];

        $new_maps = [];
        foreach ($call_map as $view => $plugin) {
            if (is_array($plugin)) {
                foreach ($plugin as $p) {
                    $this->callPluginMap($new_maps, $plugin_map, $p, $view);
                }
            } else {
                $this->callPluginMap($new_maps, $plugin_map, $plugin, $view);
            }

        }

        return $new_maps;
    }

    private function callPluginMap(&$new_maps, $plugin_map, $call, $view)
    {
        if (isset($plugin_map[$call])) {
            $get_plugin = $plugin_map[$call];
            if (is_array($get_plugin)) {

                foreach ($get_plugin as $location => $plugin) {
                    $this->setHtmlAssetsPlugin($new_maps, $location, $view, $plugin);
                }
            }
        }

    }

    private function setHtmlAssetsPlugin(&$new_maps, $location, $view, $plugin)
    {
        if (is_array($plugin)) {
            foreach ($plugin as $type => $assets) {
                if (is_array($assets)) {
                    foreach ($assets as $asset) {
                        $new_maps[$location][$type][$view][] = $asset;
                    }
                } else {
                    $new_maps[$location][$type][$view][] = $assets;
                }
            }
        }
    }


    private function setMapData($map)
    {
        if (empty($map)) return;
        foreach ($map as $key => $value) {
            $index = '_map_' . $key;
            self::$data[$index] = isset(self::$data[$index]) ? self::$data[$index] : '';
            $getHtmlAssets = $this->getMapAssets($value);

            if (!empty($getHtmlAssets)) {
                self::$data[$index] .= $getHtmlAssets;

            }

        }
    }


    private function getMapAssets($map)
    {
        $result = '';
        if (is_array($map)) {
            foreach ($map as $key => $value) {
                $result .= $this->checkMapAssets($value, $key);
            }
        }
        return $result;
    }

    private function checkMapAssets($map, $type)
    {
        $result = '';
        if (is_array($map)) {
            foreach ($map as $key => $item) {
                $key = str_replace('\\', '/', $key);

                $is_Assets_load = false;

                foreach ($this->viewsList as $view) {
                    $view = str_replace('\\', '/', $view);
                    if ($key == $view) $is_Assets_load = true;
                    if (!$is_Assets_load) {
                        if (HelperString::has($key, '*') || HelperString::has($key, ',')) {
                            $arr_key = explode('/', $key);
                            $arr_view = explode('/', $view);
                            if (count($arr_view) == count($arr_key)) {
                                $keys = $arr_key;
                                $is_Assets_load = true;
                                $i = 0;
                                foreach ($keys as $get_key) {
                                    if ($get_key == '*') {
                                        continue;
                                    } else if (HelperString::has($get_key, ',')) {
                                        $get_key = explode(',', $get_key);
                                        $check = true;
                                        foreach ($get_key as $kk) {
                                            if ($kk == $arr_view[$i]) {
                                                $check = false;
                                                break;
                                            }
                                        }
                                        if ($check) {
                                            $is_Assets_load = false;
                                            break;
                                        }
                                    } else if ($get_key != $arr_view[$i]) {
                                        $is_Assets_load = false;
                                        break;
                                    }
                                    $i++;
                                }
                            }
                        }
                    }
                }

                if (!$is_Assets_load) continue;
                $path = $this->linkBaseMapAssets;
                if (is_array($item)) {
                    foreach ($item as $_item) {
                        $result .= $this->createLoadFromAssets($type, $path, $_item);
                    }

                } else {
                    $result .= $this->createLoadFromAssets($type, $path, $item);
                }
            }
        }
        return $result;

    }

    private function createHashAssets($link)
    {
        if (HelperString::has($link, ['[hash]'])) {
            $hash = md5(HelperString::generateRandom(10));
            $link = str_replace('[hash]', $hash, $link);
        }
        return $link;
    }

    private function createLoadFromAssets($type, $path, $item)
    {
        if (!HelperString::firstHas($item, 'plugin:')) {
            $item = HelperString::firstDelete($item, 'plugin:');
            $items = explode(',', $item);
        }

        $link = $item;
        if (!HelperString::firstHas($item, ['http://', 'https://'])) {
            $link = $path . $item;
        }
        $link = $this->createHashAssets($link);

        $result = '';
        if ($type == 'js') {
            $result .= "\n" . '<script src="' . $link . '"></script>';
        } else if ($type == 'css') {
            $result .= "\n" . '<link rel="stylesheet" type="text/css" href="' . $link . '" />';
        }
        return $result;
    }

    private function addMapAssetsForJavascript()
    {
        if (!empty(self::$dataJs)) {
            $str = HelperString::generateRandom(8);
            self::$data['_map_js_cache'] = '<script src="' . Url::current() . '?pin_javascript_cache=' . $str . '.js"></script>';
        }
    }

    private static function getPathView($path)
    {
        $path = self::fixPathSeparator($path);
        $_file = self::$path . $path;
        if (!self::existsExt($_file)) $_file .= '.php';
        if (is_file($_file)) {
            return $_file;
        } else {
            if (!empty($folder = self::getInfo('extends'))) {
                $_file = self::$pathTheme . $folder . DIRECTORY_SEPARATOR . $path;
                if (!self::existsExt($_file)) $_file .= '.php';
                if (is_file($_file)) {
                    return $_file;
                }
            }
        }
        return false;
    }

    private static function existsExt($string, $start = 1, $end = 5)
    {
        return (preg_match('/\\.[^.\\s]{' . $start . ',' . $end . '}$/', $string));
    }

    /**
     * this method show one or more variable of information of current template
     * @link https://pinoox.com/documnet/pintemplate/methods/getInfo
     * @param string one or more than one variable
     * @return string|array value of one or more than one variable
     */
    public static function getInfo()
    {
        $result = self::$info;
        if (func_num_args() >= 1) {
            $args = func_get_args();
            foreach ($args as $arg) {
                if (isset($result[$arg]))
                    $result = $result[$arg];
                else
                    return null;
            }
        }
        return $result;
    }

    public function generateAssets($file, $locationSave, $recoveryTime = 86400)
    {
        if ($_file = self::getPathView('functions.php')) {
            include_once $_file;
        }
        $locationSave = self::fixPathSeparator($locationSave);
        $locationSave = self::$path . $locationSave;
        if (is_file($locationSave)) {
            $time = File::file_time($locationSave);
            if ($time > (time() - $recoveryTime))
                return;
        }
        $path = File::dir($locationSave);
        File::make_folder($path, true, 0777, false);
        $string = self::getProcessedText($file);
        File::generate($locationSave, $string);
    }

    /**
     * get html code from one file and set user short codes in that file and render it
     * @link https://pinoox.com/documnet/pintemplate/methods/getProcessedText
     * @param string $view : file name with or not with path
     * @param array $replaceData [optional] [default : null] : 2D array with indexes is name of variabel and replace in html with {[name of variable]}
     * @return  string html of file that compile
     */
    public function getProcessedText($view, $replaceData = array())
    {
        if ($_file = self::getPathView('functions.php')) {
            include_once $_file;
        }

        $view = self::actIncludeText($view);

        if (!empty($view)) {
            ob_start(array($this, 'minifyOutput'));

            if (empty($replaceData))
                $data = self::$data;
            else
                $data = array_merge(self::$data, $replaceData);
            extract($data);
            include $view;
            $html = ob_get_clean();
            if (!empty($replaceData)) {
                foreach ($replaceData as $key => $val) {
                    if (is_array($val)) continue;
                    $html = str_replace('{' . strtoupper($key) . '}', $val, $html);
                }
            }

            return $html;
        }
        return null;
    }

    private static function actIncludeText($path)
    {
        $file = null;
        if (empty($path)) return $file;

        if (is_array($path)) {
            foreach ($path as $getPath) {
                if ($_file = self::getPathView($getPath)) {
                    return $_file;
                }
            }
        } else {
            if ($_file = self::getPathView($path)) {
                return $_file;
            }
        }
        return $file;
    }

    public function phpToAssets($file)
    {
        if ($_file = self::getPathView('functions.php')) {
            include_once $_file;
        }

        $string = self::getProcessedText($file);
        if (HelperString::has($string, '[parser-php]')) {
            $string = preg_replace('/(([\'"])(?:(?!\2|\\\\).|\\\\.)*\2)|\/\/[^\n]*|\/\*(?:[^*]|\*(?!\/))*\*\//', '$1', $string);
            echo $string;
        }
        exit;
    }

    public function loader($params)
    {
        $file = implode('/', $params);
        $ext = File::extension($file);
        $mime = Config::get('~loader' . $ext);
        HelperHeader::contentType($mime, 'UTF-8');
        self::phpToAssets($file);
    }

    public function getMeta($key = null)
    {
        if (!file_exists(self::$path . 'meta.json'))
            return null;

        $meta = json_decode(file_get_contents(self::$path . 'meta.json'), true);

        if (!empty($key) && isset($meta[$key]))
            return $meta[$key];

        return null;
    }

    public function minify($status = true)
    {
        $this->isMinify = $status;
    }

    public function getTemplateNames($excludes = [])
    {
        $templates = File::get_folder_names(self::$pathTheme);
        $templates = array_diff($templates, $excludes);
        return $templates;
    }

    public function getTemplates($excludes = [])
    {
        $names = self::getTemplateNames($excludes);
        if (empty($names)) return null;

        $templates = [];
        foreach ($names as $name) {
            $path = self::$pathTheme . $name . self::DS;
            $metaFile = $path . 'meta.json';

            if (!is_file($metaFile))
                continue;

            $metaJson = file_get_contents($metaFile);
            $meta = json_decode($metaJson, true);

            if (empty($meta))
                continue;

            $meta['cover'] = Url::file($path . $meta['cover']);
            $meta['title'] = $meta['title'][Lang::current()];
            $meta['description'] = $meta['description'][Lang::current()];
            $meta['is_enable'] = $name === self::$folder;

            $templates[] = $meta;
        }

        return $templates;
    }

    private function minifyOutput($buffer)
    {
        if (!$this->isMinify)
            return $buffer;
        $search = array(
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s'
        );
        $replace = array(
            '>',
            '<',
            '\\1'
        );
        if (preg_match("/\<html/i", $buffer) == 1 && preg_match("/\<\/html\>/i", $buffer) == 1) {
            $buffer = preg_replace($search, $replace, $buffer);
        }
        return $buffer;
    }
}