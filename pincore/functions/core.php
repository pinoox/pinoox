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
use Pinoox\Portal\Url;
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

if (!function_exists('platform')) {
    /**
     * Open platform container
     *
     * @return ContainerBuilder
     */
    function platform(): ContainerBuilder
    {
        return Container::platform();
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
        if ($url !== ''
            && !str_starts_with($url, 'http://')
            && !str_starts_with($url, 'https://')
            && !str_starts_with($url, '//')) {
            $url = Url::link($url);
        }

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
     * @param null $key
     * @param null $default
     * @return SessionInterface|mixed
     * @throws Exception
     */
    function session($key = null, $default = null)
    {
        $session = App::___()->session();
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

if (!function_exists('runtime')) {
    /**
     * Active HTTP App kernel (request, session, router, …).
     * For app.php manifest use app() instead.
     */
    function runtime(): \Pinoox\Component\Package\App
    {
        return App::___();
    }
}

if (!function_exists('cookie')) {
    /**
     * Create a new cookie instance or get the cookie bag.
     *
     * @param string|null $name Cookie name
     * @param string|null $value Cookie value
     * @param int|string|DateTimeInterface|null $expire Expiration time
     * @param string|null $path Cookie path
     * @param string|null $domain Cookie domain
     * @param bool|null $secure Secure flag
     * @param bool $httpOnly HTTP only flag
     * @param bool $raw Raw cookie flag
     * @param string|null $sameSite SameSite attribute
     * @return CookieSymfony|InputBag Returns Cookie instance or InputBag if no name provided
     * @throws Exception
     */
    function cookie(
        ?string $name = null,
        ?string $value = null,
        int|string|\DateTimeInterface|null $expire = 0,
        ?string $path = '/',
        ?string $domain = null,
        ?bool $secure = null,
        bool $httpOnly = true,
        bool $raw = false,
        ?string $sameSite = CookieAlias::SAMESITE_LAX
    ): CookieSymfony|InputBag
    {
        return $name === null
            ? App::___()->cookie()
            : Cookie::create(
                name: $name,
                value: $value,
                expire: $expire,
                path: $path,
                domain: $domain,
                secure: $secure,
                httpOnly: $httpOnly,
                raw: $raw,
                sameSite: $sameSite
            );
    }
}