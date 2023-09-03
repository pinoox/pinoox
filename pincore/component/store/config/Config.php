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

class Config
{
    private ConfigStrategyInterface $strategy;

    public function __construct(ConfigStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
        $this->strategy->load();
    }

    public function save(): void
    {
        $this->strategy->save();
    }

    public function get(string $key = null, $default = null): mixed
    {
        return $this->strategy->get($key, $default);
    }

    public function add(string $key, mixed $value): Config
    {
        $this->strategy->add($key, $value);
        return $this;
    }

    public function set(string $key, mixed $value): Config
    {
        $this->strategy->set($key, $value);
        return $this;
    }


    public function remove(string $key): Config
    {
        $this->strategy->remove($key);
        return $this;
    }

    public function merge(array $array): Config
    {
        $this->strategy->merge($array);
        return $this;
    }

    public function reset(): Config
    {
        $this->strategy->reset();
        return $this;
    }

    public function restore(): Config
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
