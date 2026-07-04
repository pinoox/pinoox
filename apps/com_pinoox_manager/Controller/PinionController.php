<?php

namespace App\com_pinoox_manager\Controller;

use App\com_pinoox_manager\Component\PackagePaths;
use Pinoox\Component\Pinion\Concerns\PinionUploadActions;
use Pinoox\Component\Kernel\Controller\ApiController;

class PinionController extends ApiController
{
    use PinionUploadActions;

    protected function pinionDefaults(): array
    {
        return [
            'destination' => PackagePaths::MANUAL,
            'extensions' => ['pinx'],
            'mode' => 'storage',
            'storage' => true,
            'record' => false,
        ];
    }
}
