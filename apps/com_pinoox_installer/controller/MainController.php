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

use Illuminate\Database\Eloquent\Builder;
use Pinoox\Component\Helpers\HelperHeader;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Model\UserModel;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Lang;
use Pinoox\Portal\View;

class MainController extends Controller
{
    public function __construct()
    {
        $this->setLang();
    }

    public function home()
    {
        $username = 'yoosef';
        $user = UserModel::where(function (Builder $builder) use($username){
            $builder->where('email',$username)->orWhere('username',$username);
        });
        $user = $user->first();
        $user->makeHidden('password');

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
    
