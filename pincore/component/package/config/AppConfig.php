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


namespace pinoox\component\package\config;


use pinoox\component\store\config\Config;

class AppConfig implements ConfigInterface
{

    public function __construct(private Pinker $pinker, private array $defaultData = [])
    {
        $fileData = $this->pinker->pickup();
        $data = array_merge($this->defaultData, $fileData);
        $this->pinker->data($data);
        return new Config($pinker);
    }


    /**
     * @inheritDoc
     */
    public function get(?string $key = null): mixed
    {
        return $this->data->get($key);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): static
    {
        $this->data->set($key, $value);

        return $this;
    }

    /**
     * Add data in config
     *
     * @param string $key
     * @param mixed $value
     * @return AppManager
     */
    public function add(string $key, mixed $value): static
    {
        $this->data->add($key, $value);

        return $this;
    }

    /**
     * Remove data in config
     *
     * @param string $key
     * @return AppManager
     */
    public function remove(string $key): static
    {
        $this->data->remove($key);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function save(): static
    {
        $this->pinker->data($this->data->get())->bake();

        return $this;
    }
}