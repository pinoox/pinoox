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

namespace pinoox\component\package;

use Exception;
use pinoox\component\helpers\Str;
use pinoox\component\package\engine\AppEngine;
use pinoox\component\store\Config;
use pinoox\component\Url;


class AppRouter
{

    public function __construct(
        private Config    $appRouteConfig,
        private AppEngine $appEngine
    )
    {
    }

    /**
     * Set default route
     *
     * @param $packageName
     */
    public function setDefault($packageName)
    {
        $this->set('*', $packageName);
    }

    /**
     * @param string|null $url
     * @return AppLayer
     */
    public function find(string|null $url = null): AppLayer
    {
        $apps = $this->get();
        $packageName = null;
        $path = null;

        // set app default
        if (isset($apps['/'])) {
            if ($this->stable($apps['/'])) {
                $packageName = $apps['/'];
            }
            unset($apps['/']);
        }

        // set app current
        $url = empty($url) ? Url::parts() : $url;
        $parts = !empty($url) ? Str::explodeDropping('/', $url) : [];
        foreach ($parts as $part) {
            $part = '/' . $part;
            if (isset($apps[$part])) {
                $package = $apps[$part];
                if ($this->stable($package)) {
                    $path = $part;
                    $packageName = $package;
                    break;
                }
            }
        }

        $path = !empty($path) ? $path : '';
        return new AppLayer($path, $packageName);
    }


    public function stable(string $packageName): bool
    {
        $enable = false;

        if ($this->appEngine->exists($packageName)) {
            try {
                $enable = (bool)$this->appEngine->config($packageName)->get('enable');
            } catch (Exception $e) {
            }
        }

        return $enable === true;
    }

    /**
     * Set route
     *
     * @param $url
     * @param $packageName
     */
    public function set($url, $packageName)
    {
        $this->appRouteConfig
            ->set($url, $packageName)
            ->save();
    }

    /**
     * Delete route by URL
     *
     * @param $url
     */
    public function delete(string $url)
    {
        $this->appRouteConfig
            ->delete($url)
            ->save();
    }

    /**
     * Delete route by Package Name
     *
     * @param $packageName
     */
    public function deletePackage(string $packageName)
    {
        $routes = $this->get();
        $keys = array_keys($routes, $packageName);
        foreach ($keys as $key) {
            unset($routes[$key]);
        }

        $this->appRouteConfig
            ->data($routes)
            ->save();
    }

    /**
     * Get routes
     *
     * @param string|null $value
     * @return mixed
     */
    public function get(?string $value = null): mixed
    {
        return $this->appRouteConfig
            ->get($value);
    }

    /**
     * set data
     *
     * @param mixed|null $data
     * @return void
     */
    public function setData(mixed $data = null)
    {
        $this->appRouteConfig
            ->data($data)
            ->save();
    }

    /**
     * Get routes by Package Name
     *
     * @param string $packageName
     * @return array|null
     */
    public function getPackage(string $packageName): ?array
    {
        $routes = $this->get();
        return array_filter($routes, function ($route) use ($packageName) {
            return $route === $packageName;
        });
    }

    /**
     * Exists a route by URL
     *
     * @param string $url
     * @return bool
     */
    public function exists(string $url): bool
    {
        return !empty($this->get($url));
    }

    /**
     * Exists a route by Package Name
     *
     * @param string $packageName
     * @return bool
     */
    public function existsPackage(string $packageName): bool
    {
        return !empty($this->getPackage($packageName));
    }
}