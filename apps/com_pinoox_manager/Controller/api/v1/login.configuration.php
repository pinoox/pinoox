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
namespace App\com_pinoox_manager\Controller\api\v1;

use Pinoox\Component\User;

class LoginConfiguration extends MasterConfiguration
{
    public function __construct()
    {
        parent::__construct();
        if(!User::isLoggedIn())
        {
            $this->error();
        }
    }
}
