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


namespace Pinoox\Flow;


use Pinoox\Component\Flow\Flow;
use Pinoox\Component\Http\Request;

class StartSessionFlow extends Flow
{
    protected function before(Request $request): void
    {
        if ($request->hasSession())
            $request->getSession()->start();
    }
}