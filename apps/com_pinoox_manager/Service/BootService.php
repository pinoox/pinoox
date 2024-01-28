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


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Service\Service;
use Pinoox\Portal\View;

class BootService extends Service
{
    protected function before(Request $request): void
    {
        $this->setLang();
    }

    private function setLang()
    {
        $lang = [
            'manager' => rlang('manager'),
            'user' => rlang('user'),
            'setting' => [
                'account' => rlang('setting>account'),
                'dashboard' => rlang('setting>dashboard'),
                'market' => rlang('setting>market'),
                'router' => rlang('setting>router'),
                'appManager' => rlang('setting>appManager'),
            ],
            'widget' => [
                'clock' => rlang('widget>clock'),
                'storage' => rlang('widget>storage'),
            ],
        ];

        View::set('_direction', @$lang['manager']['direction']);
        View::set('_lang', Str::encodeJson($lang, true));
    }

}