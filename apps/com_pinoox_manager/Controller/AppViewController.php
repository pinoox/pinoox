<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  * *   *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_manager\Controller;

use App\com_pinoox_manager\Component\AppHelper;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Transport\TransportConfig;
use Pinoox\Portal\Auth;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Lang;
use Pinoox\Portal\SubApp;
use Pinoox\Portal\Url;
use Pinoox\Portal\View;

class AppViewController
{
    public function run(Request $request, string $packageName, string $subPath = '')
    {
        Auth::boot();

        $managerToken = $request->queryOne('__manager_token');
        if (is_string($managerToken) && $managerToken !== '') {
            $tokenKey = $this->resolveTokenKeyFromJwt($managerToken);
            Auth::setRequestToken($tokenKey ?? $managerToken);
        }

        if (!Auth::check()) {
            return $this->appViewError(
                t('manager.app_view_open_error'),
                t('manager.app_view_login_required'),
            );
        }

        if (!$this->canPreview($packageName)) {
            return $this->appViewError(
                t('manager.app_view_open_error'),
                t('manager.app_view_not_available'),
                loginUrl: null,
            );
        }

        if (is_string($managerToken) && $managerToken !== '') {
            Auth::persistClientJwt($managerToken);
        }

        $mountPath = 'app/' . $packageName;

        try {
            return SubApp::run($packageName, $mountPath, $request);
        } catch (\Throwable $e) {
            return $this->appViewError(
                t('manager.app_view_open_error'),
                t('manager.app_view_not_available'),
                loginUrl: null,
            );
        }
    }

    private function authenticate(): bool
    {
        return Auth::check();
    }

    /**
     * Decode a manager JWT and return the inner token_key.
     * The JWT payload is keyed by the active auth.cookie name (e.g. manager_pinoox).
     */
    private function resolveTokenKeyFromJwt(string $jwt): ?string
    {
        if (!class_exists('Firebase\\JWT\\JWT')) {
            return null;
        }

        try {
            $config = \Pinoox\Component\User\AuthConfig::resolve(refresh: false);
            $secret = (string) ($config['jwt_secret'] ?? '');
            $keyName = (string) ($config['key'] ?? '');

            if ($secret === '' || $keyName === '') {
                return null;
            }

            $payload = (array) \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($secret, 'HS256'));
            $value = $payload[$keyName] ?? reset($payload);

            return is_string($value) && $value !== '' ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function canPreview(string $packageName): bool
    {
        if (!AppEngine::exists($packageName))
            return false;

        $app = AppHelper::getOne($packageName);

        if (!$app || empty($app['enable']))
            return false;

        if (($app['open'] ?? '') === 'app-view')
            return true;

        if (!empty($app['sys-app']) || !empty($app['sys_app']))
            return false;

        return true;
    }

    private function appViewError(string $message, ?string $hint = null, ?string $loginUrl = null): string
    {
        $locale = Lang::locale();

        return View::render('app-view-error', [
            'message' => $message,
            'hint' => $hint,
            'login_url' => $loginUrl ?? url('login'),
            'back_url' => url('/'),
            'locale' => $locale,
            'dir' => t('manager.direction'),
            'title' => t('manager.error'),
        ]);
    }
}