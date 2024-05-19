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


namespace Pinoox\Flow;


use Pinoox\Component\Http\Request;
use Pinoox\Component\Flow\Flow;
use Pinoox\Portal\Database\DB;

class TransactionalFlow extends Flow
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