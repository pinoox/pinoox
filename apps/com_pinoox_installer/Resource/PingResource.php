<?php

namespace App\com_pinoox_installer\Resource;

use Pinoox\Component\Http\Api\ApiResource;

final class PingResource extends ApiResource
{
    public function toArray(): array
    {
        return [
            'ok' => true,
            'routing' => true,
            'timestamp' => (int) ($this->resource['timestamp'] ?? time()),
        ];
    }
}
