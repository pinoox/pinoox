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


namespace Pinoox\Component\Helpers;


class Data
{
    private mixed $data;
    private array $merge;

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    private function prepareDataByKey(string $key)
    {
        $parts = explode('.', $key);
        $temp = &$this->data;

        foreach ($parts as $part) {
            if (!isset($temp[$part])) {
                $temp[$part] = [];
            } elseif (!is_array($temp[$part])) {
                $temp[$part] = [$temp[$part]];
            }
            $temp = &$temp[$part];
        }
    }

    public function add(string $key, mixed $value): static
    {
        $this->prepareDataByKey($key);

        $dataForKey = &$this->data[$key];
        if (!is_array($dataForKey)) {
            $dataForKey = [$dataForKey];
        }

        $dataForKey[] = $value;
        $this->data[$key] = $dataForKey;

        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->prepareDataByKey($key);

        $this->data[$key] = $value;

        return $this;
    }

    public function merge(mixed $data)
    {
        $this->merge[] = $data;
    }

    public function get(?string $key = null): mixed
    {
        return $this->pullData($key, true);
    }

    public function getData(?string $key = null): mixed
    {
        return $this->pullData($key, false);
    }

    private function pullData(?string $key = null, $isMerge = false): mixed
    {
        $data = $this->data;

        if (is_array($data) && $isMerge && !empty($this->merge)) {
            $merge = $this->merge;
            $merge[] = $data;
            $data = array_merge(...$merge);
        }

        if (is_null($key)) return $data;

        $parts = explode('.', $key);
        if (is_array($data)) {
            foreach ($parts as $value) {
                if (isset($data[$value])) {
                    $data = $data[$value];
                } else {
                    $data = null;
                    break;
                }
            }
        } else {
            $data = null;
        }

        return $data;
    }

    public function remove(string $key): static
    {
        $this->prepareDataByKey($key);

        unset($this->data[$key]);

        return $this;
    }
}