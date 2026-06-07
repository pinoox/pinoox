<?php

namespace Pinoox\Component\Path;

use Pinoox\Component\Template\Theme\ThemeAssets;
use Pinoox\Component\Template\Theme\ThemeManifest;
use Pinoox\Component\Template\Theme\ThemeReference;
use Pinoox\Component\Template\Theme\ThemeStack;

/**
 * Fluent theme accessor backed by theme.php and theme paths.
 *
 * @example theme('spark').assets('index.html')
 * @example theme().name
 * @example theme().title
 * @example theme().config('api')
 */
final class ThemeAccessor
{
    private ?ThemeManifest $manifest = null;
    private bool $manifestLoaded = false;

    public function __construct(
        private readonly Url $url,
        private readonly ?string $package = null,
        private readonly ?string $themeName = null,
    ) {
    }

    /** Theme folder name. */
    public function name(): string
    {
        return $this->manifest()?->name() ?? ($this->themeName ?? ThemeStack::activeName($this->resolvedPackage()));
    }

    /** Twig: theme().name */
    public function getName(): string
    {
        return $this->name();
    }

    /** Host app package from theme.php. */
    public function package(): string
    {
        $host = $this->manifest()?->hostPackage();

        return $host !== '' ? $host : $this->resolvedPackage();
    }

    /** Manifest value(s) from theme.php (supports dot notation). */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        $manifest = $this->manifest();

        return $manifest !== null
            ? $manifest->config($key, $default)
            : ($key === null || $key === '' ? [] : $default);
    }

    /** Localized title from theme.php. */
    public function title(?string $locale = null): string
    {
        return $this->manifest()?->title($locale) ?? $this->name();
    }

    /** Localized description from theme.php. */
    public function description(?string $locale = null): string
    {
        return $this->manifest()?->description($locale) ?? '';
    }

    /**
     * Localized manifest string: no key → title; "title" / "description" → theme.php fields.
     */
    public function lang(?string $key = null, ?string $locale = null): string
    {
        if ($key === null || $key === '') {
            return $this->title($locale);
        }

        return match ($key) {
            'title', 'name' => $this->title($locale),
            'description', 'desc' => $this->description($locale),
            default => (string) ($this->config($key, '') ?? ''),
        };
    }

    /** @return list<string> */
    public function extends(): array
    {
        return $this->manifest()?->extends() ?? [];
    }

    public function cover(): string
    {
        return $this->manifest()?->cover() ?? '';
    }

    public function coverUrl(): string
    {
        $cover = $this->cover();

        return $cover !== '' ? $this->assets($cover) : '';
    }

    public function versionName(): string
    {
        return $this->manifest()?->versionName() ?? '1.0';
    }

    public function versionCode(): int
    {
        return $this->manifest()?->versionCode() ?? 1;
    }

    /** Filesystem path to the theme folder (theme.php path). */
    public function root(): string
    {
        return $this->manifest()?->path()
            ?? ThemeStack::directory($this->name(), $this->resolvedPackage());
    }

    /** Absolute public URL to the theme folder. */
    public function url(): string
    {
        return $this->assets('');
    }

    /** Path-only public URL to the theme folder. */
    public function path(): string
    {
        return $this->assetsPath('');
    }

    /** Public URL to a file inside the theme folder. */
    public function assets(string $file = ''): string
    {
        return $this->build($file, false);
    }

    /** Path-only public URL to a file inside the theme folder. */
    public function assetsPath(string $file = ''): string
    {
        return $this->build($file, true);
    }

    /** App manifest accessor for the host package. */
    public function app(): AppAccessor
    {
        return $this->url->appAccessor($this->package());
    }

    /** Scope accessor to another package (keeps current theme name). */
    public function forPackage(?string $package): self
    {
        return new self($this->url, $package, $this->themeName);
    }

    public function manifest(): ?ThemeManifest
    {
        if ($this->manifestLoaded) {
            return $this->manifest;
        }

        $this->manifestLoaded = true;
        $this->manifest = ThemeManifest::load(
            $this->resolvedPackage(),
            $this->name(),
            ThemeStack::pathTheme($this->resolvedPackage()),
        );

        return $this->manifest;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'package' => $this->package(),
            'title' => $this->title(),
            'description' => $this->description(),
            'extends' => $this->extends(),
            'url' => $this->url(),
            'path' => $this->path(),
            'root' => $this->root(),
            'cover' => $this->cover(),
            'coverUrl' => $this->coverUrl(),
            'versionName' => $this->versionName(),
            'versionCode' => $this->versionCode(),
        ];
    }

    private function resolvedPackage(): string
    {
        return $this->url->activePackage($this->package);
    }

    private function build(string $file, bool $asPath): string
    {
        $package = $this->resolvedPackage();
        ['file' => $file, 'theme' => $theme] = ThemeAssets::parseThemedLink($file, $this->themeName);
        $theme = $theme ?? $this->themeName ?? ThemeStack::activeName($package);

        $ref = ThemeReference::parse($theme, $package);
        $segment = ThemeAssets::publicSegment($ref, $file, ThemeStack::pathTheme($ref->package));

        return $asPath
            ? $this->url->assetPath($segment, $ref->package)
            : $this->url->asset($segment, $ref->package);
    }
}
