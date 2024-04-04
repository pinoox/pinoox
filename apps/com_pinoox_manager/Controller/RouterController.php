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
    public function get()
    {

        $routes = AppRouter::get();
        if (!empty($routes)) {
            foreach ($routes as $alias => $packageName) {
                $app = AppHelper::getOne($packageName);
                $app['package'] = $packageName;
                $app['is_lock'] = ($alias === 'manager');
                $routes[$alias] = $app;
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
        $alias = $request->json->get('aliasName');

        if ($alias == '*')
            return $this->message('', false);

        AppRouter::delete($alias);
        return $this->message('');
    }

    public function setPackageName(Request $request)
    {
        $data = $request->json->all('alias,packageName');

        if ($data['alias'] == 'manager')
            return $this->message(t('manager.request_not_valid'), false);

        $package = AppHelper::getOne($data['packageName']);
        if (empty($package) || !$package['router'])
            return $this->message(t('manager.request_not_valid'), false);


        if ($package['router']['type'] !== 'multiple' && AppRouter::existByPackage($data['packageName']))
            return $this->message(t('manager.request_not_valid'), false);

        if (!AppRouter::exists($data['alias']))
            return $this->message(t('setting/router.no_choose_any_route'), false);


        AppRouter::set($data['alias'], $data['packageName']);
        return $this->message('', true);
    }
}