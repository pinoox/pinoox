<?php

namespace Pinoox\Component\Http\Api;

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

    /**
     * @param class-string<static> $resourceClass
     */
    public static function collection(iterable $items, string $resourceClass): array
    {
        $resources = [];

        foreach ($items as $item) {
            $resources[] = (new $resourceClass($item))->toArray();
        }

        return $resources;
    }
}

