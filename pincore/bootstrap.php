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
define('PINOOX_CORE_PATH', __DIR__ . DS);
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


$composer = require dirname(__DIR__) . '/vendor/autoload.php';

\Symfony\Component\ErrorHandler\Debug::enable();

Loader::boot($composer,dirname(__DIR__));
