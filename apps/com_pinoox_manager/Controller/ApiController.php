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


namespace App\com_pinoox_manager\Controller;


use Pinoox\Component\Kernel\Controller\Controller;

class ApiController extends Controller
{
    protected function message(mixed $result, bool $status = true): array
    {
        return ["status" => $status, "result" => $result];
    }

    public function notFoundError()
    {
        return $this->message('not found', 404);
    }
}