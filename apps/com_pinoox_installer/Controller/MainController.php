<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_installer\Controller;

use Pinoox\Component\Helpers\HelperHeader;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Portal\App\App;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function __construct()
    {
        $this->setLang();
    }

    public function home()
    {
        return View::render('index');
    }

    public function pinooxjs()
    {
        HelperHeader::contentType('application/javascript', 'UTF-8');
        return View::render('pinoox');
    }

    private function setLang()
    {
        $lang = App::get('lang');
        $direction = in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr';
        $data = Str::encodeJson([
            'install' => rlang('install'),
            'user' => rlang('user'),
            'language' => rlang('language'),
        ], true);

        View::set('_lang', $data);
        View::set('_direction', $direction);
        View::set('currentLang', $lang);
    }
}
    
