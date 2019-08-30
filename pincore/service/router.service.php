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

class RouterService implements ServiceInterface
{

    public function _run()
    {
        Router::start();
        Router::call();
    }

    public function _stop()
    {
    }
}

