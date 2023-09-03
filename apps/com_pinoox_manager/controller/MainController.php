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

namespace pinoox\app\com_pinoox_manager\controller;

use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\app\com_pinoox_manager\model\LangModel;
use pinoox\component\helpers\HelperHeader;
use pinoox\component\helpers\Str;
use pinoox\component\kernel\controller\Controller;
use pinoox\component\Router;
use pinoox\component\User;
use pinoox\portal\View;

class MainController extends Controller
{
    public function __construct()
    {
        parent::__construct();
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

    public function app($package_name)
    {
        if (User::isLoggedIn() && Router::existApp($package_name)) {
            $app = AppModel::fetch_by_package_name($package_name);
            if ($app['enable'] && !$app['sys-app']) {
                self::$template = null;
                User::reset();
                Router::build('manager/app/' . $package_name, $package_name);
                exit;
            }
        }
        $this->_main();
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

    public function dist()
    {
        $url = implode('/', Router::params());
        if ($url === 'pinoox.js') {
            HelperHeader::contentType('application/javascript', 'UTF-8');
            self::$template->view('dist/pinoox.js');
        } else {
            $this->_main();
        }
    }
}