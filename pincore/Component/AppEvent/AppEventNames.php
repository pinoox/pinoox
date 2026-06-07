<?php

namespace Pinoox\Component\AppEvent;

final class AppEventNames
{

    public const BOOTING = 'app.booting';

    public const BOOTED = 'app.booted';

    public const ROUTES = 'app.routes';

    public const API = 'app.api';

    public static function package(string $base, string $package): string
    {
        return $base . '.' . $package;
    }
}

