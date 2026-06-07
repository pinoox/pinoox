<?php

namespace Pinoox\Support;

class IlluminateConfigRepository
{
    public function __construct(private array $items = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    public function set(string $key, mixed $value): void
    {
        $data = &$this->items;

        foreach (explode('.', $key) as $segment) {
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }

            $data = &$data[$segment];
        }

        $data = $value;
    }

    public function all(): array
    {
        return $this->items;
    }
}

