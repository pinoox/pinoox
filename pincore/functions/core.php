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
use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;
use Pinoox\Component\Store\Cookie;
use Pinoox\Portal\Database\DB;
use Pinoox\Portal\Env;
use Pinoox\Portal\Pinker;
use Symfony\Component\HttpFoundation\Cookie as CookieAlias;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Cookie as CookieSymfony;

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
     * @param null $default
     * @return mixed|ConfigInterface|\Pinoox\Component\Store\Config\Config
     */
    function config(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $configName = array_shift($parts);
        $key = !empty($parts) ? implode('.', $parts) : null;
        $config = Config::name($configName);

        if (is_null($key)) {
            return $config;
        }

        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $config->set($name, $value);
            }

            return $config;
        }

        return $config->get($key, $default);
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

if (!function_exists('session')) {
    /**
     * @param $key
     * @param $default
     * @return SessionInterface|mixed
     */
    function session($key = null, $default = null)
    {
        $session = app()->session();
        if (is_null($key)) {
            return $session;
        }

        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $session->set($name, $value);
            }

            return $session;
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('app')) {
    /**
     * @param null $key
     * @param null $default
     * @return \Pinoox\Component\Package\App|mixed
     */
    function app($key = null, $default = null)
    {
        $app = App::___();
        if (is_null($key)) {
            return $app;
        }

        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $app->set($name, $value);
            }

            return $app;
        }

        return $app->get($key, $default);
    }
}


if (!function_exists('cookie')) {
    /**
     * Create a new cookie instance.
     *
     * @param string|null $name
     * @param string|null $value
     * @param int|string|DateTimeInterface $expire
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool $httpOnly
     * @param bool $raw
     * @param string|null $sameSite
     * @return CookieSymfony|InputBag
     */
    function cookie(string $name = null, ?string $value = null, int|string|\DateTimeInterface $expire = 0, ?string $path = '/', ?string $domain = null, ?bool $secure = null, bool $httpOnly = true, bool $raw = false, ?string $sameSite = CookieAlias::SAMESITE_LAX)
    {
        if (is_null($name)) {
            return app()->cookie();
        }

        return Cookie::create($name, $value, $expire, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }
}