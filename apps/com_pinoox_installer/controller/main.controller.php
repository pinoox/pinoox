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

use pinoox\component\app\AppProvider;
use pinoox\component\Config;
use pinoox\component\DB;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperHeader;
use pinoox\component\Lang;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\System;
use pinoox\component\User;
use pinoox\component\Validation;
use pinoox\model\PinooxDatabase;
use pinoox\model\UserModel;

class MainController extends MasterConfiguration
{
    public function _main()
    {
        self::$template->view('index');
    }

    public function _exception()
    {
        Response::redirect(url('lang'));
    }


    public function lang()
    {
        self::_main();
    }

    public function setup()
    {
        self::_main();
    }

    public function rules()
    {
        self::_main();
    }

    public function prerequisites()
    {
        self::_main();
    }

    public function db()
    {
        self::_main();
    }

    public function user()
    {
        Response::redirect(url('db'));
    }

    public function dist()
    {
        $url = implode('/', Router::params());
        if ($url === 'pinoox.js') {
            HelperHeader::contentType('application/javascript', 'UTF-8');
            self::$template->view('dist/pinoox.js');
        } else {
            self::_main();
        }
    }
}
    
