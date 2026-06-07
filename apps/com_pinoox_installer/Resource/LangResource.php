<?php

namespace App\com_pinoox_installer\Resource;

use Pinoox\Component\Http\Api\ApiResource;

final class LangResource extends ApiResource
{
    public function toArray(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'direction' => (string) ($payload['direction'] ?? 'ltr'),
            'lang' => is_array($payload['lang'] ?? null) ? $payload['lang'] : [],
        ];
    }
}

