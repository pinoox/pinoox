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


use Pinoox\Component\Flow\Flow;
use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Http\Request;

class RemoveTrailingSlashFlow extends Flow
{
    protected function handle(Request $request, \Closure $next)
    {
        $uri = $request->getUri();

        if ($request->isMethod('GET') && substr($uri, -1) === '/' && $uri !== $request->getUriForPath('/')) {
            $newUri = rtrim($uri, '/');
            return new RedirectResponse($newUri);
        }
        return $next($request);
    }
}