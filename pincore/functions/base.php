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

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Helpers\ViteHelper;
use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Kernel\ContainerBuilder;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Env;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Path;
use Pinoox\Portal\Pinker;
use Pinoox\Portal\Url;
use Pinoox\Portal\View;

if (!function_exists('url')) {
    function url(string $link = ''): string
    {
        return Url::get($link);
    }
}

if (!function_exists('assets')) {
    function assets(string $link = '', bool $isPath = false): string
    {
        $path = View::path()->assets($link);
        return $isPath ? $path : furl($path);
    }
}

if (!function_exists('alias')) {
    function alias(?string $key = null): mixed
    {
        return !empty($key) ? App::alias($key) : App::aliases();
    }
}

if (!function_exists('vite')) {
    function vite(string $name, string $fileManifest = 'dist/.vite/manifest.json')
    {
        ViteHelper::usePrintVite($name, $fileManifest);
    }
}

if (!function_exists('furl')) {
    function furl(string $path = ''): string
    {
        return Url::path($path);
    }
}

if (!function_exists('path')) {
    function path($path = '', $package = '')
    {
        return Path::get($path, $package);
    }
}

if (!function_exists('lang')) {
    function lang($key, array $replace = [], $locale = NULL, $fallback = true)
    {
        $result = Lang::get($key, $replace, $locale, $fallback);
        echo !is_array($result) ? $result : Str::encodeJson($result);
    }
}

if (!function_exists('rlang')) {
    /**
     * @deprecated Use the 't()' function instead, which provides the same functionality.
     */
    function rlang($key, array $replace = [], $locale = NULL, $fallback = true)
    {
        return Lang::get($key, $replace, $locale, $fallback);
    }
}

if (!function_exists('t')) {
    function t($key, array $replace = [], $locale = NULL, $fallback = true)
    {
        return Lang::get($key, $replace, $locale, $fallback);
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

if (!function_exists('view')) {
    /**
     * render view
     *
     * @param string $name
     * @param array $parameters
     * @return \Pinoox\Component\Template\View
     */
    function view(string $name = '', array $parameters = []): \Pinoox\Component\Template\View
    {
        return View::___()->ready($name, $parameters);
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
        if (!is_null($key))
            return Env::get($key, $default);
        else
            return $_ENV;
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