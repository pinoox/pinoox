<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\component\store;

use pinoox\component\helpers\Data;

class Config
{
    /**
     * Data config
     *
     * @var Data
     */
    private Data $data;

    /**
     * Config constructor
     *
     * @param Pinker $pinker
     * @param mixed|null $merge
     */
    public function __construct(private Pinker $pinker, private mixed $merge = null)
    {
        $this->data($pinker->pickup(), $merge);
    }

    /**
     * @param Pinker $pinker
     * @param mixed|null $merge
     * @return static
     */
    public static function create(Pinker $pinker, mixed $merge = null): static
    {
        return new static($pinker, $merge);
    }

    /**
     * Set target data in config
     *
     * @param string $pointer
     * @param string|null $key
     * @param mixed $value
     * @return Config
     */
    public function setLinear(string $pointer, ?string $key, mixed $value): Config
    {
        $data = $this->get($pointer);
        $data = is_array($data) ? $data : [];
        $data[$key] = $value;
        $this->set($pointer, $data);

        return $this;
    }

    /**
     * Get data from config
     *
     * @param string|null $key
     * @return mixed
     */
    public function get(?string $key = null): mixed
    {
        return $this->data->get($key);
    }

    /**
     * Get info from config
     *
     * @param string|null $key
     * @return array
     */
    public function getInfo(?string $key = null): array
    {
        return $this->pinker->getInfo($key);
    }

    /**
     * Set data in config
     *
     * @param string $key
     * @param mixed $value
     * @return Config
     */
    public function set(string $key, mixed $value): Config
    {
        $this->data->set($key, $value);
        return $this;
    }

    /**
     * Set data in config
     *
     * @param mixed|null $data
     * @param mixed|null $merge
     * @return Config
     */
    public function data(mixed $data, mixed $merge = null): Config
    {
        $this->data = new Data($data);

        if (!empty($merge))
            $this->data->merge($merge);

        return $this;
    }

    /**
     * Get Data
     * @return Data
     */
    public function getData(): Data
    {
        return $this->data;
    }

    /**
     * Get Pinker
     * @return Pinker
     */
    public function getPinker(): Pinker
    {
        return $this->pinker;
    }

    /**
     * Get target data from config
     *
     * @param string|null $pointer
     * @param string|null $key
     * @return mixed
     */
    public function getLinear(?string $pointer, ?string $key): mixed
    {
        $data = $this->get($pointer);
        return $data[$key] ?? null;
    }

    /**
     * Delete data in config
     *
     * @param string $key
     * @return Config
     */
    public function delete(string $key): Config
    {
        $this->data->remove($key);
        return $this;
    }

    /**
     * Delete target data in config
     *
     * @param string $pointer
     * @param string|null $key
     * @return Config
     */
    public function deleteLinear(string $pointer, ?string $key): Config
    {
        $data = $this->get($pointer);
        $data = is_array($data) ? $data : [];
        unset($data[$key]);
        $this->set($pointer, $data);

        return $this;
    }

    /**
     * Reset data
     *
     * @return Config
     */
    public function reset(): Config
    {
        $this->data(
            $this->pinker->pickup(),
            $this->merge
        );
        return $this;
    }

    /**
     * refresh data in config with config file
     *
     * @return Config
     */
    public function restore(): Config
    {
        $this->pinker->restore();
        $this->reset();
        return $this;
    }

    /**
     * Add data in config
     *
     * @param string $key
     * @param mixed $value
     * @return Config
     */
    public function add(string $key, mixed $value): Config
    {
        $this->data->add($key, $value);
        return $this;
    }

    /**
     * merge data
     * @param mixed $data
     * @return void
     */
    public function merge(mixed $data): void
    {
        $this->data->merge($data);
    }

    /**
     * Save data on config file
     *
     * @return Config
     */
    public function save(): Config
    {
        $data = $this->data->getData();
        $this->pinker->data($data)->bake();

        return $this;
    }
}
    
