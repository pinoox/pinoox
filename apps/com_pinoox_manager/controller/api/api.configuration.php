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
namespace pinoox\app\com_pinoox_manager\controller\api;

use pinoox\component\User;
use pinoox\component\interfaces\ControllerInterface;
use pinoox\component\Response;
use pinoox\component\Router;
use pinoox\component\Template;
use pinoox\component\Url;

class ApiConfiguration implements ControllerInterface
{

    public function __construct()
    {
        User::lifeTime(100,'day');
    }

    public function _main()
    {
        $this->error();
    }

    public function _exception()
    {
        $this->error();
    }

    public function error()
    {
        Response::json('not found',404);
    }
}
    
