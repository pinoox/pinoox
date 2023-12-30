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

use Closure;
use Exception;
use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Component\Package\Engine\AppEngine;

class App
{
    private ConfigInterface $config;

    public function __construct(
        private AppLayer  $appLayer,
        private AppEngine $appEngine,
    )
    {
        $this->config = $this->appEngine->config($this->package());
    }

    /**
     * Get the package name of the current application
     *
     * @return string|null
     */
    public function package(): ?string
    {
        return $this->appLayer?->getPackageName();
    }

    /**
     * Get App stake
     *
     * @return AppLayer
     */
    public function current(): AppLayer
    {
        return $this->appLayer;
    }

    /**
     * Get the URL of the current application
     *
     * @return string
     */
    public function path(): string
    {
        return $this->appLayer?->getPath();
    }

    /**
     * Set App stake
     *
     * @param AppLayer $appLayer
     */
    public function setLayer(AppLayer $appLayer)
    {
        $this->appLayer->setPath($appLayer->getPath());
        $this->appLayer->setPackageName($appLayer->getPackageName());
    }

    /**
     * @param string $packageName
     * @param Closure $closure
     * @param string $path
     * @return mixed
     * @throws Exception
     */
    public function meeting(string $packageName, Closure $closure, string $path = ''): mixed
    {
        if (!$this->exists($packageName))
            throw new Exception('package `' . $packageName . '` not found!');

        $mainLayer = new AppLayer($this->path(), $this->package());

        $this->setLayer(new AppLayer($path, $packageName));
        if (!is_callable($closure))
            throw new Exception('the value must be of function type');

        $result = $closure();

        $this->setLayer($mainLayer);

        return $result;
    }

    /**
     * Set the package name of the current application
     *
     * @param string $package
     * @throws Exception
     */
    public function setPackageName(string $package)
    {
        if (!self::exists($package))
            throw new Exception('package `' . $package . '` not found!');

        $this->appLayer?->setPackageName($package);
    }

    /**
     * Set the URL of the current application
     *
     * @param string $path
     */
    public function setPath(string $path = '')
    {
        $this->appLayer?->setPath($path);
    }


    /**
     * App exists
     * @param string $packageName
     * @return bool
     */
    public function exists(string $packageName): bool
    {
        return $this->appEngine->exists($packageName);
    }

    /**
     * Check App for use has stable
     *
     * @param string $packageName
     * @return bool
     */
    public function stable(string $packageName): bool
    {
        $enable = false;

        if ($this->exists($packageName)) {
            try {
                $enable = (bool)$this->config->get('enable');
            } catch (Exception $e) {
            }
        }

        return $enable === true;
    }

    /**
     * Get data from config current app
     *
     * @param string|null $value
     * @return mixed
     */
    public function get(?string $value = null): mixed
    {
        $packageName = $this->appLayer?->getPackageName();

        if (empty($packageName))
            return null;

        try {
            return $this->config->get($value);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Set data in config current app
     *
     * @param string $key
     * @param mixed $value
     * @return Config|null
     */
    public function set(string $key, mixed $value): ?ConfigInterface
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        $this->config->set($key, $value);
        return $this->config;
    }

    /**
     * Set data in config current app
     *
     * @param string $key
     * @param mixed $value
     * @return Config|null
     */
    public function add(string $key, mixed $value): ?ConfigInterface
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        $this->config->add($key, $value);
        return $this->config;
    }

    /**
     * Set data in config current app
     *
     * @return Config|null
     */
    public function save(): ?ConfigInterface
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        $this->config->save();
        return $this->config;
    }
}

