<?php

namespace Pinoox\Flow;

use Closure;
use Pinoox\Component\Flow\Flow;
use Pinoox\Component\Http\Request;
use Pinoox\Portal\Access;
use Pinoox\Portal\Auth;

class PermissionFlow extends Flow
{
    protected function handle(Request $request, Closure $next)
    {
        $permission = Access::routePermission(
            $request->attributes->get('_router'),
            $request->attributes->all(),
        );

        if ($permission === null || $permission === '') {
            return $next($request);
        }

        Auth::boot();

        if (!Access::can($permission)) {
            return $this->deny($request, $permission);
        }

        return $next($request);
    }

    protected function deny(Request $request, string $permission)
    {
        $message = 'You do not have permission to perform this action.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'error' => 'forbidden',
                'message' => $message,
                'permission' => $permission,
            ], 403);
        }

        return response($message, 403);
    }

    private function wantsJson(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api')
            || str_contains(strtolower($request->headers->get('Accept', '')), 'json');
    }
}

