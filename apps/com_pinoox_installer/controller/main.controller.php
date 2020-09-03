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

use pinoox\component\HelperHeader;
use pinoox\component\Response;
use pinoox\component\Router;

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
        $this->_main();
    }

    public function setup()
    {
        $this->_main();
    }

    public function rules()
    {
        $this->_main();
    }

    public function prerequisites()
    {
        $this->_main();
    }

    public function db()
    {
        $this->_main();
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
            $this->_main();
        }
    }
}
    
