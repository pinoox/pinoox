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


namespace Pinoox\Service;


use Pinoox\Component\Interfaces\ServiceInterface;
use Pinoox\Component\Kernel\Boot;

class BootService implements ServiceInterface
{

    public function _run()
    {
        (new Boot())->build();
    }

    public function _stop()
    {
    }


}