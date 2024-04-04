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

namespace Pinoox\Component\Package;

use Exception;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Store\Config\ConfigInterface;


class AppRouter
{

    public function __construct(
        private ConfigInterface $appRouteConfig,
        private EngineInterface $appEngine,
        private Request         $request
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

    private function parts(?string $index = null): string
    {
        $parts = $this->request->getPathInfo();

        if (!is_null($index)) {
            $partsArr = explode('/', $parts);
            if ($index == 'first') return reset($partsArr);
            if ($index == 'last') return end($partsArr);

            return $partsArr[$index] ?? '';
        }
        return $parts;
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
        $url = empty($url) ? $this->parts() : $url;
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
            ->remove($url)
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
            ->setData($routes)
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
            ->setData($data)
            ->save();
    }

    /**
     * Get routes by Package Name
     *
     * @param string $packageName
     * @return array|null
     */
    public function getByPackage(string $packageName): ?array
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
    public function existByPackage(string $packageName): bool
    {
        return !empty($this->getByPackage($packageName));
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return ConfigInterface
     */
    public function config(): ConfigInterface
    {
        return $this->appRouteConfig;
    }
}