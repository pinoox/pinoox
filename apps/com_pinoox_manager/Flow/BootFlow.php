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

namespace App\com_pinoox_manager\Flow;

use Pinoox\Component\Flow\Flow;
use Pinoox\Component\Helpers\PinooxScriptHelper;
use Pinoox\Component\Http\Request;
use Pinoox\Portal\Auth;
use Pinoox\Portal\View;

class BootFlow extends Flow
{
    protected function before(Request $request): void
    {
        Auth::boot();

        View::set('bootstrap', PinooxScriptHelper::bootstrap([
            'locale' => app()->lang(),
        ]));
    }
}