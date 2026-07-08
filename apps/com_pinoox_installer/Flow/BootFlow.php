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

namespace App\com_pinoox_installer\Flow;

use App\com_pinoox_installer\Component\LangHelper;
use App\com_pinoox_installer\Component\PlatformVersion;
use Pinoox\Component\Helpers\PinooxScriptHelper;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Flow\Flow;
use Pinoox\Portal\App\App;
use Pinoox\Portal\View;

class BootFlow extends Flow
{
    protected function before(Request $request): void
    {
        $this->setLang();
    }

    private function setLang()
    {
        $lang = App::get('lang');

        $direction = LangHelper::direction($lang);

        View::set('_direction', $direction);
        View::set('currentLang', $lang);
        View::set('bootstrap', PinooxScriptHelper::bootstrap([
            'locale' => $lang,
            'direction' => $direction,
            'version' => PlatformVersion::label(),
            'lang' => LangHelper::forFrontend($lang),
        ]));
    }
}