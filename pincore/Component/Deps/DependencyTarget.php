<?php

namespace Pinoox\Component\Deps;

final readonly class DependencyTarget
{
    public function __construct(
        public string $type,
        public string $scope,
        public string $path,
        public string $label,
    ) {
    }

    public function manifestPath(): string
    {
        return $this->path . '/' . ($this->type === 'composer' ? 'composer.json' : 'package.json');
    }

    public function isInstalled(): bool
    {
        return match ($this->type) {
            'composer' => is_file($this->path . '/vendor/autoload.php'),
            'npm' => is_dir($this->path . '/node_modules'),
            default => false,
        };
    }
}
