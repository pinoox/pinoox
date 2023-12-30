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

use Pinoox\Component\Kernel\ContainerBuilder;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;
use Pinoox\Portal\Path;
use Pinoox\Component\Lang;
use Pinoox\Component\Service;
use Pinoox\Component\Url;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Container;
use Pinoox\Portal\View;
use Pinoox\Component\File;
use Pinoox\Portal\Env;
use Pinoox\Portal\Pinker;

if (!function_exists('url')) {
    function url($link = null)
    {
        return Url::link($link);
    }
}

if (!function_exists('assets')) {
    function assets($link = '', $isPath = false)
    {
        $path = View::path()->assets($link);
        return $isPath ? $path : furl($path);
    }
}

if (!function_exists('vite')) {
    function vite($name = null, $fileManifest = 'dist/manifest.json')
    {
        $pathManifest = View::path()->assets($fileManifest);
        $manifest = [];
        if (is_file($pathManifest)) {
            $manifest = file_get_contents($pathManifest);
            $manifest = Str::decodeJson($manifest);
        }

        $manifest = !empty($name) ? @$manifest[$name]['file'] : $name;
        if (!empty($manifest)) {
            $dir = dirname($fileManifest);
            $url = assets($dir . '/' . $manifest);
            if (File::extension($manifest) === 'js')
                echo '<script type="module"  src="' . $url . '"></script>';
            else if (File::extension($manifest) === 'css')
                echo '<link rel="stylesheet" href="' . $url . '">';
            else
                echo $manifest;
        }
    }
}

if (!function_exists('furl')) {
    function furl($path = null)
    {
        return Url::file($path);
    }
}

if (!function_exists('path')) {
    function path($path = null, $app = null)
    {
        return Path::get($path, $app);
    }
}

if (!function_exists('lang')) {
    function lang($var)
    {
        $args = func_get_args();
        $first = array_shift($args);

        $result = Lang::replace($first, $args);

        echo !is_array($result) ? $result : Str::encodeJson($result);
    }
}

if (!function_exists('rlang')) {
    function rlang($var)
    {
        $args = func_get_args();
        $first = array_shift($args);

        return Lang::replace($first, $args);
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

if (!function_exists('service')) {
    function service($service)
    {
        return Service::run($service);
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
     * @return View
     */
    function view(string $name = '', array $parameters = []): View
    {
        return View::ready($name, $parameters);
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
     * get & set in env
     *
     * @return mixed
     */
    function _env($key = null, $value = null)
    {
        if (empty($value))
            return Env::get($key);
        else
            Env::set($key, $value);
    }
}
