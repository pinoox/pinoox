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


namespace Pinoox\Component\Store\Config\Strategy;

use Pinoox\Component\Store\Config\Data\DataManager;
use Pinoox\Component\Store\Baker\Pinker;

class FileConfigStrategy implements ConfigStrategyInterface
{

    private DataManager $dataFirstState;
    private DataManager $data;

    public function __construct(private readonly Pinker $pinker)
    {
        $this->initData($pinker);
    }

    private function initData(Pinker $pinker): void
    {
        $this->data = $this->dataFirstState = new DataManager($pinker->pickup());
    }

    public function setData(mixed $data)
    {
        $this->data = new DataManager($data);
    }

    public function getData(): DataManager
    {
        return $this->data;
    }

    public function save(): void
    {
        $this->pinker->data($this->data->getData())->bake();
    }

    public function getPinker(): Pinker
    {
        return $this->pinker;
    }

    public function set($key, $value): void
    {
        $this->data->set($key, $value);
    }

    public function get($key = null, $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    public function remove($key): void
    {
        $this->data->remove($key);
    }

    public function add(string $key, mixed $value): void
    {
        $this->data->add($key, $value);
    }

    public function reset(): void
    {
        $this->data = $this->dataFirstState;
    }

    public function restore(): void
    {
        $this->pinker->restore();
        $this->initData($this->pinker);
    }

    public function merge(array $array): void
    {
        $this->data->merge($array);
    }

    public function getInfo(?string $key = null): array|string|null
    {
        return $this->getPinker()->getInfo($key);
    }

    public function name(): string
    {
        return $this->pinker->getBakedFile();
    }
}