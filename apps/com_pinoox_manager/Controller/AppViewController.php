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

use App\com_pinoox_manager\Component\AppHelper;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Transport\TransportConfig;
use Pinoox\Portal\Auth;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Url;
use Pinoox\Portal\View;

class AppViewController
{
    public function run(Request $request, string $packageName, string $subPath = '')
    {
        Auth::boot();

        $managerToken = $request->queryOne('__manager_token');
        if (is_string($managerToken) && $managerToken !== '') {
            Auth::setRequestToken($managerToken);
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

        $hostPackage = App::package();

        if (!TransportConfig::sharesAuthWith($packageName, $hostPackage)) {
            Auth::reset();
        } else {
            Auth::boot();
        }

        if (is_string($managerToken) && $managerToken !== '') {
            Auth::persistClientJwt($managerToken);
        }

        $layerPath = App::pathRoute() . '/app/' . $packageName;

        try {
            $response = AppProvider::meetingHandle($packageName, $layerPath, $request);

            return $response instanceof Response ? $response : new Response((string) $response);
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

