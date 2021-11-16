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
use pinoox\component\Config;
use pinoox\component\HelperHeader;
use pinoox\component\HelperString;
use pinoox\component\Request;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\User;

class MainController extends MasterConfiguration
{
    public function _exception()
    {
        $this->_main();
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

    public function _main()
    {
        self::$template->view('index');
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