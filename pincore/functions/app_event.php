<?php

use Pinoox\Component\AppEvent\AppRegister;
use Pinoox\Portal\AppBoot;

if (!function_exists('app_boot')) {
    function app_boot(?string $package = null): AppRegister
    {
        return AppBoot::ensure($package);
    }
}

