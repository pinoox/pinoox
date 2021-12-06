<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Erfan Ebrahimi
 * @link http://www.erfanebrahimi.ir/
 * @license  https://opensource.org/licenses/MIT MIT License
 */
namespace pinoox\service;

use pinoox\component\console;
use pinoox\component\interfaces\ServiceInterface;
use pinoox\component\Router;
use pinoox\component\Url;

class ConsoleService implements ServiceInterface
{

    public function _run()
    {
        if ( is_null(Url::request())){
            Global $argv ;
            console::run($argv);
        }
    }

    public function _stop()
    {
    }
}

