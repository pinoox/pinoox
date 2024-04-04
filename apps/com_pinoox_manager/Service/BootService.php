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


namespace App\com_pinoox_manager\Service;


use App\com_pinoox_manager\Component\LangHelper;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Service\Service;
use Pinoox\Component\User;
use Pinoox\Portal\View;

class BootService extends Service
{
    protected function before(Request $request): void
    {
        User::lifeTime(100, 'day');
        $this->setLang();
    }

    private function setLang()
    {
        $lang = LangHelper::all();

        View::set('_direction', @$lang['manager']['direction']);
        View::set('_lang', Str::encodeJson($lang, true));
    }

}