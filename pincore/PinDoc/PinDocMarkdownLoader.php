<?php

namespace Pinoox\PinDoc;

use Pinoox\Portal\App\AppEngine;

class PinDocMarkdownLoader
{
    public function __construct(
        private readonly PinDocMarkdownConverter $converter = new PinDocMarkdownConverter(),
    ) {
    }

    public function resolveForPackage(string $package, array $docs, ?string $cliPath = null): string
    {
        $paths = [];

        if ($cliPath !== null && trim($cliPath) !== '') {
            $paths[] = $cliPath;
        }

        foreach (['markdown', 'markdown_extra', 'md', 'md_extra'] as $key) {
            $configured = trim((string)($docs[$key] ?? ''));
            if ($configured !== '') {
                $paths[] = $configured;
            }
        }

        $markdown = '';

        foreach ($paths as $path) {
            $content = $this->read($package, $path);
            if ($content === '') {
                continue;
            }

            $extra = $this->converter->extractExtraBlocks($content);
            if ($extra === '' && !str_contains($content, 'pindoc:extra')) {
                $extra = $content;
            }

            $markdown = $markdown === '' ? $extra : $markdown . "\n\n" . $extra;
        }

        return trim($markdown);
    }

    public function read(string $package, string $path): string
    {
        $resolved = $this->resolvePath($package, $path);

        if (!is_file($resolved)) {
            throw new \RuntimeException('Markdown file not found: ' . $path);
        }

        $content = file_get_contents($resolved);

        return is_string($content) ? $content : '';
    }

    public function convertFile(string $package, string $path): string
    {
        return $this->converter->convert($this->read($package, $path));
    }

    public function resolvePath(string $package, string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($this->isAbsolute($path) || str_starts_with($path, 'apps/')) {
            return $this->resolveProjectPath($path);
        }

        return AppEngine::path($package, $path);
    }

    private function resolveProjectPath(string $path): string
    {
        if ($this->isAbsolute($path)) {
            return $path;
        }

        return rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/') . '/' . ltrim($path, '/');
    }

    private function isAbsolute(string $path): bool
    {
        return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/');
    }
}

