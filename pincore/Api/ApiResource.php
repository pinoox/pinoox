<?php

namespace Pinoox\Api;

use JsonSerializable;

abstract class ApiResource implements JsonSerializable
{
    public function __construct(protected mixed $resource)
    {
    }

    abstract public function toArray(): array;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function collection(iterable $items): array
    {
        $resources = [];

        foreach ($items as $item) {
            $resources[] = (new static($item))->toArray();
        }

        return $resources;
    }
}
