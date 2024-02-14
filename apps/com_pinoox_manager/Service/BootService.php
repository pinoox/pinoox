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
            'manager' => t('manager'),
            'user' => t('user'),
            'setting' => [
                'account' => t('setting>account'),
                'dashboard' => t('setting>dashboard'),
                'market' => t('setting>market'),
                'router' => t('setting>router'),
                'appManager' => t('setting>appManager'),
            ],
            'widget' => [
                'clock' => t('widget>clock'),
                'storage' => t('widget>storage'),
            ],
        ];

        View::set('_direction', @$lang['manager']['direction']);
        View::set('_lang', Str::encodeJson($lang, true));
    }

}