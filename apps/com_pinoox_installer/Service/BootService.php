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


namespace App\com_pinoox_installer\Service;


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Service\Service;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Lang;
use Pinoox\Portal\View;

class BootService extends Service
{
    protected function before(Request $request): void
    {
        $this->setLang();
    }

    private function setLang()
    {
        $lang = App::get('lang');
        $direction = in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr';
        $data = Str::encodeJson([
            'install' => t('install'),
            'user' => t('user'),
            'language' => t('language'),
        ], true);

        View::set('_lang', $data);
        View::set('_direction', $direction);
        View::set('currentLang', $lang);
    }
}