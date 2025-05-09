<?php

namespace Pinoox\Component;

use Pinoox\Component\Package\Engine\AppEngine;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;

class Mode
{
    private ?string $appMode = null;
    private ?string $defaultMode = 'production';
    private AppEngine $appEngine;

    public function __construct(AppEngine $appEngine, ?string $defaultMode = null, ?string $appMode = null)
    {
        $this->appEngine = $appEngine;

        if ($defaultMode) {
            $this->defaultMode = $defaultMode;
        }

        $this->appMode = $appMode ?? $this->defaultMode;
    }

    public function get(?string $package = null): ?string
    {
        if ($package) {
            $mode = $this->appEngine->config($package)->get('mode');
            if (!empty($mode)) {
                return $mode;
            }

            return $this->defaultMode;
        }
        return $this->appMode;
    }

    public function is(string $mode, ?string $package = null): bool
    {
        return self::get($package) === $mode;
    }
} 