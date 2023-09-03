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

use pinoox\component\kernel\Loader;

define('DS', DIRECTORY_SEPARATOR);
define('PINOOX_START', microtime(true));
define('PINOOX_DEFAULT_LANG', 'en');
define('PINOOX_PATH', dirname(__DIR__) . DS);
define('PINOOX_VENDOR_PATH', PINOOX_PATH . 'vendor' . DS);
define('PINOOX_CORE_PATH', __DIR__ . DS);
define('PINOOX_FUNCTIONS_PATH', PINOOX_CORE_PATH . 'functions' . DS);
define('PINOOX_MODEL_PATH', PINOOX_CORE_PATH . 'model' . DS);
define('PINOOX_COMPONENT_PATH', PINOOX_CORE_PATH . 'component' . DS);
define('PINOOX_SERVICE_PATH', PINOOX_CORE_PATH . 'service' . DS);
define('PINOOX_CONFIG_PATH', PINOOX_CORE_PATH . 'config' . DS);
define('PINOOX_LANG_PATH', PINOOX_CORE_PATH . 'lang' . DS);
define('PINOOX_APP_PATH', realpath(__DIR__ . DS . '..') . DS . 'apps' . DS);
define('PINOOX_PATH_THUMB', 'thumbs/{name}_{size}.{ext}');


/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here, so we don't need to manually load our classes.
|
*/
$composer = require PINOOX_VENDOR_PATH . 'autoload.php';
$loader = new \pinoox\component\kernel\LoaderManager($composer);

Loader::boot($composer);