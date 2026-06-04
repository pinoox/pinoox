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

require_once __DIR__ . '/core-path.php';

require_once __DIR__ . '/requirements.php';
pinoox_check_runtime_requirements();

use Pinoox\Portal\App\AppProvider;

define('PINOOX_START', microtime(true));


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


require_once PINOOX_CORE_PATH . 'functions/base.php';

$loader = require PINOOX_BASE_PATH . '/vendor/autoload.php';

if ($loader instanceof Composer\Autoload\ClassLoader) {
    $loader->addPsr4('Pinoox\\', PINOOX_CORE_PATH, true);
}

AppProvider::boot();
