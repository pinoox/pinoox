<?php

namespace Pinoox\Component\Template\Reference;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Path\Manager\PathManager;

class TemplatePathReference
{
    private bool $absolute;

    public function __construct(
        private string|array $folders,
        private string $themePath = '',
    ) {
        $this->absolute = $themePath === '' && $this->hasAbsolutePaths($folders);
    }

    public function current(): string
    {
        if ($this->absolute) {
            $folder = is_array($this->folders) ? $this->folders[0] : $this->folders;

            return Str::ds((string) $folder);
        }

        $folder = is_string($this->folders) ? $this->folders : @$this->folders[0];

        return Str::ds($this->themePath . '/' . $folder);
    }

    public function all(): array
    {
        if ($this->absolute) {
            $paths = is_array($this->folders) ? $this->folders : [$this->folders];

            return array_map(static fn ($path) => Str::ds((string) $path), $paths);
        }

        $paths = [];
        if (is_array($this->folders)) {
            foreach ($this->folders as $folder) {
                $paths[] = Str::ds($this->themePath . '/' . $folder);
            }
        } else {
            $paths[] = Str::ds($this->themePath . '/' . $this->folders);
        }

        return $paths;
    }

    public function assets(string $file = ''): string
    {
        if ($f = $this->file($file)) {
            return $f;
        }

        $pathManager = new PathManager($this->current());

        return $pathManager->get($file);
    }

    public function file(string $file): bool|string
    {
        $paths = $this->all();
        foreach ($paths as $path) {
            $filePath = Str::ds($path . '/' . $file);
            if (is_file($filePath)) {
                return $filePath;
            }
        }

        return false;
    }

    public function exists(string $file): bool
    {
        return (bool) $this->file($file);
    }

    private function hasAbsolutePaths(string|array $folders): bool
    {
        $items = is_array($folders) ? $folders : [$folders];

        foreach ($items as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }

            if (str_starts_with($item, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $item) !== 0) {
                return true;
            }
        }

        return false;
    }
}

