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
    require_once __DIR__ . '/core-autoload.php';
    pinoox_register_core_autoload($loader, PINOOX_BASE_PATH, PINOOX_CORE_PATH);
}

\Pinoox\Component\Helpers\EnvBootstrap::load(PINOOX_BASE_PATH);

\Pinoox\Component\File::ensureStorageRootHtaccess(\Pinoox\Support\SystemConfig::path('storage'));
\Pinoox\Support\SystemConfig::ensureProjectConfigFiles();

AppProvider::boot();
