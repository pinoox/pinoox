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


namespace pinoox\component\store\config\data;

/**
 * Represents a data structure that allows nested key-value pairs and provides various operations for manipulation.
 */
class DataArray implements DataInterface
{
    private mixed $data;
    private array $merge;

    public function __construct(mixed $data = [])
    {
        $this->setData($data);
    }

    public function merge(array $data): void
    {
        $this->merge[] = $data;
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        $value = $this->fetchData($key, true);

        return $value !== null ? $value : $default;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(mixed $data = []): void
    {
        $this->data = $data;
    }

    private function fetchData(?string $key = null, bool $isMerge = false)
    {
        $data = $this->data;

        if (is_array($data) && $isMerge && !empty($this->merge)) {
            $merge = $this->merge;
            $merge[] = $data;
            $data = array_merge(...$merge);
        }

        if (is_null($key)) {
            return $data;
        }

        return $this->getNestedValue($data, $key);
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function restore(): void
    {
        $this->reset();;
    }

    public function add(string $key, mixed $value): void
    {
        $this->addNestedValue($this->data, $key, $value);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setNestedValue($this->data, $key, $value);
    }

    public function remove(string $key): void
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $parentKey = implode('.', $keys);

        $parentValue = $this->getNestedValue($this->data, $parentKey) ?? $key;

        if (is_array($parentValue) && isset($parentValue[$lastKey])) {
            unset($parentValue[$lastKey]);
            $this->setNestedValue($this->data, $parentKey, $parentValue);
        } else {
            unset($this->data[$key]);
        }
    }

    public function fromArray(array $arr): static
    {
        foreach ($arr as $key => $value) {
            $this->data->set($key, $value);
        }

        return $this;
    }

    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $temp = &$array;

        foreach ($keys as $nestedKey) {
            if (!isset($temp[$nestedKey]) || !is_array($temp[$nestedKey])) {
                $temp[$nestedKey] = [];
            }
            $temp = &$temp[$nestedKey];
        }

        $temp = $value;
    }

    private function addNestedValue(array &$array, string $key, $value): void
    {
        $existingValue = $this->getNestedValue($array, $key);

        if (is_array($existingValue)) {
            $existingValue[] = $value;
            $this->setNestedValue($array, $key, $existingValue);
        } else {
            $this->setNestedValue($array, $key, $value);
        }
    }

    private function getNestedValue(mixed $array, string $key)
    {
        if(!is_array($array))
            return null;
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $nestedKey) {
            if (isset($value[$nestedKey])) {
                $value = $value[$nestedKey];
            } else {
                return null;
            }
        }

        return $value;
    }

}