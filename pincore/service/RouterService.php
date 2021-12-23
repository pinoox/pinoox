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
namespace pinoox\service;

use pinoox\component\interfaces\ServiceInterface;
use pinoox\component\Router;
use pinoox\component\Url;

class RouterService implements ServiceInterface
{

    public function _run()
    {
        if ( ! is_null(Url::request())){
            Router::start();
            Router::call();
        }
    }

    public function _stop()
    {
    }
}

