<?php

namespace Tests\Feature\Support;

interface KernelSampleContract
{
    public function label(): string;
}

class KernelSampleService implements KernelSampleContract
{
    public function label(): string
    {
        return 'kernel-sample';
    }
}

