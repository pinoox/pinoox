<?php

namespace Pinoox\Component\Path;

use Pinoox\Component\Package\AppManifest;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\Lang;

/**
 * Fluent app manifest accessor (app.php).
 *
 * @example app().name
 * @example app().theme().name
 * @example app('com_pinoox_welcome').url()
 */
final class AppAccessor
{
    /** @var array<string, mixed>|null */
    private ?array $manifestCache = null;

    public function __construct(
        private readonly Url $url,
        private readonly ?string $package = null,
    ) {
    }

    public function __toString(): string
    {
        return $this->url();
    }

    /** Package id (com_vendor_app). */
    public function package(): string
    {
        return AppManifest::package($this->resolvedPackage());
    }

    /** Twig: app().package */
    public function getPackage(): string
    {
        return $this->package();
    }

    /** Display name from app.php. */
    public function name(): string
    {
        return (string) ($this->manifest()['name'] ?? $this->package());
    }

    /** Twig: app().name */
    public function getName(): string
    {
        return $this->name();
    }

    /** Active theme folder name from app.php. */
    public function themeName(): string
    {
        $theme = $this->config('theme', 'default');

        if (is_array($theme)) {
            $name = $theme['name'] ?? $theme['theme'] ?? null;

            return is_string($name) && $name !== '' ? $name : 'default';
        }

        return is_string($theme) && $theme !== '' ? $theme : 'default';
    }

    /**
     * Manifest value(s) from app.php (supports dot notation).
     */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        return AppManifest::get($this->resolvedPackage(), $key, $default);
    }

    /**
     * Default locale from app.php or app translation key.
     */
    public function lang(?string $key = null, ?string $locale = null, array $replace = []): string
    {
        if ($key === null || $key === '') {
            return (string) $this->config('lang', 'en');
        }

        $line = Lang::get($key, $replace, $locale);

        return is_string($line) ? $line : (string) json_encode($line);
    }

    /** Filesystem path under the app folder. */
    public function root(string $relative = ''): string
    {
        $package = $this->resolvedPackage();
        $base = AppEnginePortal::path($package);

        if ($relative === '') {
            return rtrim(str_replace('\\', '/', $base), '/');
        }

        return rtrim(str_replace('\\', '/', $base), '/') . '/' . ltrim($relative, '/');
    }

    /** Absolute public app route URL (same as url()->app()). */
    public function url(): string
    {
        return rtrim($this->url->forApp($this->resolvedPackage()), '/');
    }

    /** Path-only public app route URL (same as url()->appPath()). */
    public function path(): string
    {
        return $this->url->appPath($this->resolvedPackage());
    }

    /** @deprecated Use path() */
    public function urlPath(): string
    {
        return $this->path();
    }

    /** Public file URL under apps/{package}/. */
    public function resource(string $path = ''): string
    {
        return $this->url->asset($path, $this->resolvedPackage());
    }

    /** Path-only public file URL under apps/{package}/. */
    public function resourcePath(string $path = ''): string
    {
        return $this->url->assetPath($path, $this->resolvedPackage());
    }

    public function icon(): string
    {
        return (string) $this->config('icon', 'icon.png');
    }

    public function iconUrl(): string
    {
        $icon = ltrim($this->icon(), '/');

        if ($icon === '') {
            return $this->resource('resources/default.png');
        }

        return $this->url->asset($icon, $this->resolvedPackage());
    }

    public function versionName(): string
    {
        return (string) ($this->config('version-name') ?? $this->config('version', '1.0'));
    }

    public function versionCode(): int
    {
        return (int) ($this->config('version-code', 1));
    }

    /** Theme accessor for this app (defaults to active theme from app.php). */
    public function theme(?string $name = null): ThemeAccessor
    {
        return $this->url->themeAccessor($name, $this->resolvedPackage());
    }

    /** Scope accessor to another package. */
    public function scope(?string $package): self
    {
        return new self($this->url, $package);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'package' => $this->package(),
            'name' => $this->name(),
            'theme' => $this->themeName(),
            'lang' => $this->lang(),
            'url' => $this->url(),
            'path' => $this->path(),
            'root' => $this->root(),
            'versionName' => $this->versionName(),
            'versionCode' => $this->versionCode(),
            'icon' => $this->icon(),
            'iconUrl' => $this->iconUrl(),
        ];
    }

    private function resolvedPackage(): string
    {
        return $this->url->activePackage($this->package);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        return $this->manifestCache ??= AppManifest::load($this->resolvedPackage());
    }
}
