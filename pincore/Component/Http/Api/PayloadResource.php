<?php

namespace Pinoox\Component\Http\Api;

/**
 * Wraps array/object payloads for API responses.
 */
final class PayloadResource extends ApiResource
{
    public function toArray(): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        if (is_object($this->resource) && method_exists($this->resource, 'toArray')) {
            return $this->resource->toArray();
        }

        return ['value' => $this->resource];
    }
}

