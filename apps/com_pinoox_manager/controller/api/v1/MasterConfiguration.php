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

use Pinoox\Component\Kernel\Controller\Controller;
use Pinoox\Component\User;

class MasterConfiguration extends Controller
{
    const manualPath = 'downloads/packages/manual/';

    public function __construct()
    {
        User::lifeTime(100, 'day');
    }

    protected function message($result, $status)
    {
        return ["status" => $status, "result" => $result];
    }


    public function error()
    {
        return $this->message('not found', 404);
    }
}

