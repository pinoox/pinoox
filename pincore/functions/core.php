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

use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Kernel\ContainerBuilder;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Env;
use Pinoox\Portal\Pinker;

if (!function_exists('alias')) {
    function alias(?string $key = null): mixed
    {
        return !empty($key) ? App::alias($key) : App::aliases();
    }
}

if (!function_exists('config')) {
    /**
     * get or set config
     *
     * @param string $key
     * @return mixed|null
     */
    function config(string $key)
    {
        $parts = explode('.', $key);
        $name = array_shift($parts);
        $key = !empty($parts) ? implode('.', $parts) : null;
        $config = Config::name($name);
        $args = func_get_args();
        if (isset($args[1]))
            $config->set($key, $args[1]);
        else
            return $config->get($key);

        return null;
    }
}

if (!function_exists('app')) {
    function app($key = null)
    {
        return App::get($key);
    }
}

if (!function_exists('pinker')) {
    /**
     * Save data & info in pinker
     *
     * @param mixed $data
     * @param array $info
     * @return array
     */
    function pinker(mixed $data, array $info = []): array
    {
        return Pinker::build($data, $info);
    }
}

if (!function_exists('cache')) {
    /**
     * Cache data in pinker
     *
     * @param mixed $data
     * @param int $lifetime seconds
     * @return array
     */
    function cache(mixed $data, int $lifetime): array
    {
        $info = $lifetime ? ['lifetime' => $lifetime] : [];
        return Pinker::build($data, $info);
    }
}

if (!function_exists('container')) {
    /**
     * Open app container
     *
     * @param string|null $packageName
     * @return ContainerBuilder
     */
    function container(?string $packageName = null): ContainerBuilder
    {
        return Container::app($packageName);
    }
}

if (!function_exists('pincore')) {
    /**
     * Open pincore container
     *
     * @return ContainerBuilder
     */
    function pincore(): ContainerBuilder
    {
        return Container::pincore();
    }
}

if (!function_exists('_env')) {
    /**
     * get envs
     */
    function _env(?string $key = null, $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }
}

if (!function_exists('response')) {
    function response(?string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('transaction')) {
    /**
     * @throws Throwable
     */
    function transaction(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}