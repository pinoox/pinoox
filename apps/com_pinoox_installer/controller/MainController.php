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

namespace pinoox\app\com_pinoox_installer\controller;

use Illuminate\Database\Eloquent\Builder;
use pinoox\component\helpers\HelperHeader;
use pinoox\component\helpers\Str;
use pinoox\component\kernel\controller\Controller;
use pinoox\component\User;
use pinoox\model\UserModel;
use pinoox\portal\app\App;
use pinoox\portal\View;

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
    
