<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Path;

use Pinoox\Component\Template\Theme\ThemeAssets;

/**
 * Fluent URL accessor for the active request and app route.
 *
 * Primary Twig pattern (see docs/pinoox-urls.md):
 *
 * @example url().app()      → app route URL (PINOOX.URL.APP)
 * @example url().appPath() → app route path (PINOOX.URL.BASE)
 * @example url().api()     → API base URL
 * @example url().resource('resources/avatar.png')
 * @example assets()        → active theme base URL (PINOOX.URL.THEME)
 */
final class UrlAccessor
{
    public const DEFAULT_API_PREFIX = 'api/v1';

    public function __construct(
        private readonly Url $url,
        private readonly ?string $package = null,
    ) {
    }

    public function __toString(): string
    {
        return $this->app();
    }

    /** Host name without scheme (e.g. domain.com). */
    public function domain(): string
    {
        return $this->url->host();
    }

    /** Absolute site origin (scheme + host + project base path). */
    public function site(): string
    {
        return rtrim($this->url->origin(), '/');
    }

    /** Absolute app base URL (site origin + app router segment). */
    public function app(): string
    {
        return rtrim($this->url->forApp($this->package), '/');
    }

    /**
     * Public path to the project root (leading slash, never empty).
     * Root install → "/"; subfolder install → "/pinoox".
     */
    public function path(): string
    {
        return $this->url->sitePath();
    }

    /**
     * Public path to the app base (project path + app segment).
     * Root install → "/manager"; subfolder → "/pinoox/manager".
     */
    public function appPath(): string
    {
        return $this->url->appPath($this->package);
    }

    /** App route segment from App Router (e.g. "manager"). */
    public function routeSegment(): string
    {
        return $this->url->routeSegment($this->package);
    }

    /**
     * Absolute API base URL under the active app (default prefix: api/v1/).
     *
     * @example url()->api()              → …/manager/api/v1/
     * @example url()->api('auth/login')  → …/manager/api/v1/auth/login
     */
    public function api(string $path = '', string $prefix = self::DEFAULT_API_PREFIX): string
    {
        return $this->url->link($this->apiSegment($path, $prefix), Url::SCOPE_APP);
    }

    /**
     * Public path to the app API base (path-only, for SPA routers).
     *
     * @example url()->apiPath() → /pinoox/manager/api/v1/
     */
    public function apiPath(string $path = '', string $prefix = self::DEFAULT_API_PREFIX): string
    {
        return $this->url->link($this->apiSegment($path, $prefix), Url::SCOPE_APP_PATH);
    }

    /**
     * Public file URL under the active app route (resources, uploads, …).
     *
     * @example url()->resource('resources/avatar.png') → …/apps/com_pinoox_manager/resources/avatar.png
     */
    public function resource(string $path = ''): string
    {
        return $this->url->asset($path, $this->package);
    }

    /**
     * Path-only public file URL under the active app route.
     *
     * @example url()->resourcePath('resources/') → /pinoox/apps/com_pinoox_manager/resources/
     */
    public function resourcePath(string $path = ''): string
    {
        return $this->url->assetPath($path, $this->package);
    }

    /**
     * Theme asset URL (same as the global assets() helper).
     *
     * @example url()->assets('index.html')
     * @example url()->assets('@default/logo.png')
     */
    public function assets(string $file = '', bool $asPath = false, ?string $theme = null): string
    {
        return ThemeAssets::resolveWithUrl($this->url, $file, $theme, $this->package, $asPath);
    }

    /** Path-only theme asset URL. */
    public function assetsPath(string $file = '', ?string $theme = null): string
    {
        $resolved = ThemeAssets::resolveSegment($file, $theme, $this->package, $this->url);

        return $this->url->assetPath($resolved['segment'], $resolved['package']);
    }

    /** Build a URL path/absolute link (same as the global url($path) helper). */
    public function link(string $path = '', string $scope = Url::SCOPE_APP, string $mode = Url::MODE_AUTO): string
    {
        return $this->url->link($path, $scope, $mode);
    }

    /** Scope accessor to another package. */
    public function package(?string $package): self
    {
        return new self($this->url, $package);
    }

    /**
     * @return array{domain: string, site: string, app: string, path: string, appPath: string, routeSegment: string, api: string, apiPath: string, resource: string, resourcePath: string, theme: string, themePath: string, resources: string, avatar: string, appIcon: string}
     */
    public function toArray(): array
    {
        return [
            'domain' => $this->domain(),
            'site' => $this->site(),
            'app' => $this->app(),
            'path' => $this->path(),
            'appPath' => $this->appPath(),
            'routeSegment' => $this->routeSegment(),
            'api' => $this->api(),
            'apiPath' => $this->apiPath(),
            'resource' => $this->resource(),
            'resourcePath' => $this->resourcePath(),
            'theme' => $this->assets(''),
            'themePath' => $this->assetsPath(''),
            'resources' => $this->resource('resources/'),
            'avatar' => $this->resource('resources/avatar.png'),
            'appIcon' => $this->resource('resources/default.png'),
        ];
    }

    private function apiSegment(string $path, string $prefix): string
    {
        $prefix = trim($prefix, '/');
        $segment = $prefix === '' ? '' : $prefix;

        if ($path !== '') {
            return $segment === '' ? ltrim($path, '/') : $segment . '/' . ltrim($path, '/');
        }

        return $segment === '' ? '' : $segment . '/';
    }
}
