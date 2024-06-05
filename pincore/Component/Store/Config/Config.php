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

namespace Pinoox\Component\Store\Config;

use Pinoox\Component\Store\Config\Strategy\ConfigStrategyInterface;

class Config implements ConfigInterface
{
    private ConfigStrategyInterface $strategy;

    /**
     * @var ConfigInterface[]
     */
    private static array $configs = [];

    public function __construct(ConfigStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public static function create(ConfigStrategyInterface $strategy): static
    {
        if (empty(self::$configs[$strategy->name()]))
            self::$configs[$strategy->name()] = new static($strategy);
        return self::$configs[$strategy->name()];
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

    public function all(): mixed
    {
        return $this->strategy->all();
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

    /**
     * Set target data in config
     *
     * @param string $pointer
     * @param string|null $key
     * @param mixed $value
     * @return static
     */
    public function setLinear(?string $pointer, ?string $key, mixed $value): static
    {
        $data = $this->get($pointer);
        $data = is_array($data) ? $data : [];
        $data[$key] = $value;

        if (!empty($pointer)) {
            $this->set($pointer, $data);
        } else {
            $this->setData($pointer, $data);

        }

        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->strategy->setData($data);
        return $this;
    }


    public function remove(string $key): static
    {
        $this->strategy->remove($key);
        return $this;
    }

    /**
     * Remove target data in config
     *
     * @param string $pointer
     * @param string|null $key
     * @return static
     */
    public function removeLinear(string $pointer, ?string $key): static
    {
        $data = $this->get($pointer);
        $data = is_array($data) ? $data : [];
        unset($data[$key]);
        $this->set($pointer, $data);

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

    /**
     * Get info from config
     *
     * @param string|null $key
     * @return array
     */
    public function getInfo(?string $key = null): array
    {
        return $this->getStrategy()->getInfo($key);
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

    public function getStrategy(): ConfigStrategyInterface
    {
        return $this->strategy;
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function __toString(): string
    {
        return json_encode($this->all());

    }

    public function __invoke()
    {
        return $this->all();
    }
}
