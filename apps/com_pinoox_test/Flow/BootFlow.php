<?php
/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_test\Flow;

use Pinoox\Component\Flow\Flow;
use Pinoox\Component\Http\Request;

class BootFlow extends Flow
{
    protected function before(Request $request): void
    {
        // App-wide boot logic (auth, locale, …)
    }
}
