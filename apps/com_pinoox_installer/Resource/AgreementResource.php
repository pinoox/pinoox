<?php

namespace App\com_pinoox_installer\Resource;

use Pinoox\Component\Http\Api\ApiResource;

final class AgreementResource extends ApiResource
{
    public function toArray(): array
    {
        return [
            'text' => (string) $this->resource,
        ];
    }
}
