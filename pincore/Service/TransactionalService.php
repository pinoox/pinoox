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


namespace Pinoox\Service;


use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Service\Service;
use Pinoox\Portal\Database\DB;

class TransactionalService extends Service
{
    public function handle(Request $request, \Closure $next): mixed
    {
        DB::beginTransaction();
        $response = $next($request);
        if ($response->getStatusCode() < 400) {
            DB::commit();
        } else {
            DB::rollBack();
        }
        return $response;
    }
}