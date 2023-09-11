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
use pinoox\component\store\config\ConfigInterface;
use pinoox\component\package\engine\AppEngine;

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
        $this->appLayer = $appLayer;
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
    public function add(string $key, mixed $value): ?Config
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
    public function save(): ?Config
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        $this->config->save();
        return $this->config;
    }
}

