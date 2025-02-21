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

class RouterController extends ApiController
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

    public function add(Request $request)
    {
        $alias = $request->json->get('alias');
        if (empty($alias) || Str::has($alias, ['?', '\\', '>', '<', '!', '=', '~', '*', '#']))
            return $this->message(t('setting/router.write_correct_url'), false);

        if (AppRouter::exists($alias))
            return $this->message(t('setting/router.this_url_exists_before'), false);

        $alias = '/' . Str::firstDelete($alias, '/');
        AppRouter::set($alias, '');
        return $this->message('');
    }

    public function remove(Request $request)
    {
        $path = $request->json->get('path','');

        if ($path == '/' || empty($path))
            return $this->message(t('manager.request_not_valid'), false);

        AppRouter::delete($path);
        return $this->message(t('manager.deleted_successfully'), true);
    }

    public function setPackageName(Request $request)
    {
        $data = $request->json('path,packageName,oldPath');
        $isEdit = !empty($data['oldPath']);

        if ($data['path'] == 'manager')
            return $this->message(t('manager.request_not_valid'), false);

        $package = AppHelper::getOne($data['packageName']);
        if (empty($package) || !$package['router'])
            return $this->message(t('manager.request_not_valid'), false);


        if ($package['router']['type'] !== 'multiple' && AppRouter::existByPackage($data['packageName']))
            return $this->message(t('manager.request_not_valid'), false);

        if ($data['path'] !== $data['oldPath'] && AppRouter::exists($data['path']))
            return $this->message(t('setting/router.no_choose_any_route'), false);


        if ($isEdit) {
            AppRouter::delete($data['oldPath']);
        }
        AppRouter::set($data['path'], $data['packageName']);
        return $this->message($isEdit ? t('manager.edited_successfully') : t('manager.added_successfully'), true);
    }
}