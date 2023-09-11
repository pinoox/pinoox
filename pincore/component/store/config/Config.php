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

namespace pinoox\component\store\config;

use pinoox\component\store\config\strategy\ConfigStrategyInterface;
use pinoox\component\store\baker;

class Config implements ConfigInterface
{
    private ConfigStrategyInterface $strategy;

    public function __construct(ConfigStrategyInterface $strategy)
    {
        $this->create($strategy);
    }

    public function create(ConfigStrategyInterface $strategy): static
    {
        $this->strategy = $strategy;

        return $this;
    }

    public function save(): static
    {
        $this->strategy->save();

        return $this;
    }

    public function get(string $key = null, $default = null): mixed
    {
        return $this->strategy->get($key, $default);
    }

    public function add(string $key, mixed $value): static
    {
        $this->strategy->add($key, $value);
        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->strategy->set($key, $value);
        return $this;
    }


    public function remove(string $key): static
    {
        $this->strategy->remove($key);
        return $this;
    }

    public function merge(array $array): static
    {
        $this->strategy->merge($array);
        return $this;
    }

    public function reset(): static
    {
        $this->strategy->reset();
        return $this;
    }

    public function restore(): static
    {
        $this->strategy->restore();
        return $this;
    }

    public function setLinear(string $key, string $target, mixed $value)
    {
        $this->strategy->set($key . '.' . $target, $value);
        return $this;
    }

    public function getLinear(string $key, string $target)
    {
        $this->strategy->get($key . '.' . $target);
        return $this;
    }

    public function getPinker(): baker\Pinker
    {
        return $this->strategy->getPinker();
    }


}
