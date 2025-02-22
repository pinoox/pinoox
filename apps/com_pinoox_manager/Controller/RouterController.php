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
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Portal\App\AppRouter;

class RouterController extends Api
{
    public function getAll()
    {

        $routes = AppRouter::get();
        if (!empty($routes)) {
            foreach ($routes as $path => $packageName) {
                $routes[$path] = [
                    'path' => $path,
                    'package' => $packageName,
                    'is_lock' => ($path === '/manager'),
                ];
            }
        }
        return $routes;
    }

    public function remove(Request $request)
    {
        $path = $request->json->get('path', '');

        if ($path == '/' || empty($path))
            return $this->error('manager.request_not_valid');

        AppRouter::delete($path);
        return $this->message('manager.deleted_successfully');
    }

    public function save(Request $request)
    {
        $data = $request->json('path,packageName,oldPath');
        $isEdit = !empty($data['oldPath']);

        if (empty($data['path'])) {
            return $this->error('setting/router.no_choose_any_route');
        }

        $data['path'] = !Str::firstHas($data['path'], '/') ? '/' . $data['path'] : $data['path'];

        if ($data['path'] == '/manager')
            return $this->error('manager.request_not_valid');

        $package = AppHelper::getOne($data['packageName']);
        if (empty($package) || (isset($package['router']) && !$package['router']))
            return $this->error('setting/router.no_can_route_package');


        if ($package['router']['type'] !== 'multiple' && AppRouter::existByPackage($data['packageName']))
            return $this->error('setting/router.no_multiple_package');

        if ($data['path'] !== $data['oldPath'] && AppRouter::exists($data['path']))
            return $this->error('setting/router.this_url_exists_before');


        if ($isEdit) {
            AppRouter::delete($data['oldPath']);
        }
        AppRouter::set($data['path'], $data['packageName']);
        return $this->message($isEdit ? 'manager.edited_successfully' : 'manager.added_successfully');
    }
}