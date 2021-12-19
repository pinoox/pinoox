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
namespace pinoox\app\com_pinoox_welcome\controller;

use pinoox\component\interfaces\ControllerInterface;
use pinoox\component\Response;
use pinoox\component\Template;

class MasterConfiguration implements ControllerInterface{

    protected static $template;

    public function __construct()
    {
        self::$template = new Template();
    }

    public function _main()
    {
        Response::redirect(url());
    }

    public function _exception()
    {
        Response::redirect(url());
    }
}
