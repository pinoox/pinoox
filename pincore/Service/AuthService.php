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


use Closure;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Service\Service;
use Pinoox\Component\Router\Route;
use Pinoox\Component\User;

abstract class AuthService extends Service
{
    final protected function handle(Request $request, Closure $next)
    {
        $route = $request->attributes->get('_router');

        if ($this->validate($request, $route) && $this->checkExcludeRequestUri($request, $route) && $this->checkIncludeRequestUri($request, $route) && !User::isLoggedIn()) {

            $exit = $this->exit(
                $request,
                $route,
            );
            if ($exit !== true && !is_null($exit)) {
                return $exit;
            }
        }

        return $next($request);
    }

    private function checkExcludeRequestUri(Request $request, ?Route $route): bool
    {
        $excludeItems = $this->exclude($request, $route);
        if (is_array($excludeItems)) {
            foreach ($excludeItems as $excludeItem) {
                if (str_starts_with($request->getRequestUri(), $excludeItem)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function checkIncludeRequestUri(Request $request, ?Route $route): bool
    {
        $includeItems = $this->include($request, $route);
        if (is_array($includeItems)) {
            foreach ($includeItems as $includeItem) {
                if (str_starts_with($request->getRequestUri(), $includeItem)) {
                    return true;
                }
            }

            return false;
        }
        return true;
    }

    protected function exclude(Request $request, ?Route $route): ?array
    {
        return null;
    }

    protected function include(Request $request, ?Route $route): ?array
    {
        return null;
    }

    protected function validate(Request $request, $route): bool
    {
        return true;
    }

    abstract protected function exit(Request $request, Route $route);
}