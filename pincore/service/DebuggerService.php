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
use Symfony\Component\ErrorHandler\Debug;

class DebuggerService implements ServiceInterface
{

    public function _run()
    {
        Debug::enable();
    }

    public function _stop()
    {
    }
}