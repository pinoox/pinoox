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
            return redirect(url('login'));
        }

        if (!$this->canPreview($packageName)) {
            return redirect(url('/'));
        }

        $hostPackage = App::package();

        if (!TransportConfig::sharesAuthWith($packageName, $hostPackage)) {
            Auth::reset();
        }

        $layerPath = App::pathRoute() .'/app/' . $packageName;

        try {
            $response = AppProvider::meetingHandle($packageName, $layerPath);

            return $response instanceof Response ? $response : new Response((string) $response);
        } catch (\Throwable) {
            return View::render('main');
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
}

