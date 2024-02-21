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

namespace App\com_pinoox_manager\Controller\api\v1;

use App\com_pinoox_manager\Controller\AppHelper;
use Pinoox\Component\Config;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Request;
use Pinoox\Component\Response;
use Pinoox\Component\Validation;

class RouterController extends LoginConfiguration
{

    public function get()
    {
        $routes = Config::get('~app');
        if (!empty($routes)) {
            foreach ($routes as $alias => $packageName) {
                $app = AppHelper::fetch_by_package_name($packageName);
                $app['package'] = $packageName;
                $app['is_lock'] = ($alias === 'manager');
                $routes[$alias] = $app;
            }
        }
        Response::json($routes);
    }

    public function add()
    {
        $alias = Request::inputOne('alias');
        $routes = Config::get('~app');
        if (empty($alias) || Str::has($alias, ['?', '\\', '>', '<', '!', '=', '~', '*', '#']))
            Response::json(t('setting>router.write_correct_url'), false);

        if (isset($routes[$alias]))
            Response::json(t('setting>router.this_url_exists_before'), false);

        Config::setLinear('~app', $alias, '');
        Config::save('~app');

        Response::json('', true);
    }

    public function remove()
    {
        $alias = Request::inputOne('aliasName');
        if ($alias == '*')
            Response::json('', false);

        Config::removeLinear('~app',$alias);
        Config::save('~app');

        Response::json('', true);
    }

    public function setPackageName()
    {
        $routes = Config::get('~app');
        $data = Request::input('alias,packageName');

        if ($data['alias'] == 'manager')
            Response::json(t('manager.request_not_valid'), false);

        $package = AppHelper::fetch_by_package_name($data['packageName']);
        if (empty($package) || !$package['router'])
            Response::json(t('manager.request_not_valid'), false);


        if ($package['router'] !== 'multiple' && is_array($routes) && in_array($data['packageName'], $routes))
            Response::json(t('manager.request_not_valid'), false);

        if (!Validation::checkOne($data['alias'], 'required') || !isset($routes[$data['alias']]))
            Response::json(t('setting>router.no_choose_any_route'), false);

        Config::setLinear('~app', $data['alias'], $data['packageName']);
        Config::save('~app');

        Response::json('', true);
    }

}
