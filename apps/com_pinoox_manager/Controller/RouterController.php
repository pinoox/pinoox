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
use App\com_pinoox_manager\Component\AppRoutePolicy;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppRouter;

class RouterController extends ApiController
{
    public function getAll()
    {
        $routeMap = AppRouter::routes();
        $routes = [];

        foreach ($routeMap as $path => $packageName) {
            $routes[$path] = $this->formatRouteEntry($path, $packageName);
        }

        if (!isset($routes['/'])) {
            $routes['/'] = $this->formatRouteEntry(
                '/',
                AppRouter::find('/')->getPackageName(),
                isImplicit: true,
            );
        }

        return $routes;
    }

    /**
     * @return array{path: string, package: string, is_lock: bool, is_home: bool, is_implicit?: bool}
     */
    private function formatRouteEntry(string $path, string $packageName, bool $isImplicit = false): array
    {
        $entry = [
            'path' => $path,
            'package' => $packageName,
            'is_lock' => $path === '/manager',
            'is_home' => $path === '/',
        ];

        if ($isImplicit) {
            $entry['is_implicit'] = true;
        }

        return $entry;
    }

    public function remove(Request $request)
    {
        $path = $request->payload('path', '');

        if ($path == '/' || empty($path))
            return $this->error('manager.request_not_valid');

        AppRouter::delete($path);
        return $this->message('manager.deleted_successfully');
    }

    public function save(Request $request)
    {
        $data = $request->payloadMany('path,packageName,oldPath');
        $isEdit = !empty($data['oldPath']);

        if (empty($data['path'])) {
            return $this->error('setting/router.no_choose_any_route');
        }

        $data['path'] = !Str::firstHas($data['path'], '/') ? '/' . $data['path'] : $data['path'];

        if ($data['path'] == '/manager')
            return $this->error('manager.request_not_valid');

        $package = AppHelper::getOne($data['packageName']);
        $routerConfig = AppEngine::config($data['packageName'])->get('router');

        if (empty($package) || !AppRoutePolicy::isRoutable($routerConfig))
            return $this->error('setting/router.no_can_route_package');

        if (!AppRoutePolicy::allowsMultiple($routerConfig)) {
            $existingPaths = array_keys(AppRouter::getByPackage($data['packageName']));

            if (!$isEdit && count($existingPaths) > 0) {
                return $this->error('setting/router.no_multiple_package');
            }

            if ($isEdit) {
                $oldPackage = AppRouter::get($data['oldPath']);

                if ($oldPackage !== $data['packageName'] && count($existingPaths) > 0) {
                    return $this->error('setting/router.no_multiple_package');
                }
            }
        }

        if ($data['path'] !== $data['oldPath'] && AppRouter::exists($data['path']))
            return $this->error('setting/router.this_url_exists_before');

        if ($isEdit && !empty($data['oldPath']) && $data['oldPath'] !== $data['path']) {
            AppRouter::delete($data['oldPath']);
        }

        AppRouter::set($data['path'], $data['packageName']);
        return $this->message($isEdit ? 'manager.edited_successfully' : 'manager.added_successfully');
    }
}
