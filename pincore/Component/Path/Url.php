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


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\App;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Component\Path\Manager\UrlManager;
use Pinoox\Component\Router\QueryRouteResolver;

class Url implements UrlInterface
{
    public function __construct(
        private readonly App       $app,
        private readonly Request   $request,
        private readonly AppRouter $appRouter,
        private string             $basePath,
    )
    {
    }

    public function host(): string
    {
        return $this->request->getHost();
    }

    public function httpHost(): string
    {
        return $this->request->getHttpHost();
    }

    public function scheme(): string
    {
        return $this->request->getScheme();
    }

    public function port(): string
    {
        return $this->request->getPort();
    }

    public function scriptName(): string
    {
        return $this->request->getScriptName();
    }

    public function method(): string
    {
        return $this->request->getMethod();
    }

    public function realMethod(): string
    {
        return $this->request->getRealMethod();
    }

    public function clientIp(): string
    {
        return $this->request->getClientIp();
    }

    public function userAgent(): ?string
    {
        return $this->request->server->get('HTTP_USER_AGENT');
    }

    public function clientIps(): array
    {
        return $this->request->getClientIps();
    }

    public function base(): string
    {
        $basePath = $this->request->getBasePath();

        if ($basePath !== '') {
            return $basePath;
        }

        return $this->stripFrontController($this->request->getBaseUrl());
    }

    public function params(): string
    {
        return $this->request->getPathInfo();
    }

    public function route($name, $parameters = [], bool $isFullBase = true): string
    {
        static $urlManager = new UrlManager();
        $urlManager->setBasePath($this->site($isFullBase));
        return $urlManager->get($this->app->router()->path($name, $parameters));
    }

    public function parameters(): array
    {
        return array_filter(explode('/', $this->params()));
    }

    public function site(bool $isFullBase = true): string
    {
        if ($isFullBase) {
            $site = $_ENV['HOST_PROXY'] ?? $this->request->getUriForPath('');
        } else {
            $site = $this->base();
        }

        if ($this->isSsl() && str_contains($site, 'http')) {
            $site = str_replace('http:', 'https:', $site);
        }

        return $this->stripFrontController($site);
    }

    private function stripFrontController(string $value): string
    {
        if ($value === '' || !str_contains($value, 'index.php')) {
            return $value;
        }

        $stripped = preg_replace('#/index\.php(?=/|$)#', '', $value);

        return is_string($stripped) && $stripped !== '' ? $stripped : $value;
    }

    private function isSsl(): bool
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS']))
                return true;
            if ('1' == $_SERVER['HTTPS'])
                return true;
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }

    public function app(bool $isFullBase = true): string
    {
        $route = Str::firstDelete($this->app->pathRoute(), '/');
        if ($isFullBase)
            return !empty($route) ? $this->site() . '/' . $route : $this->site();
        else
            return !empty($route) ? $this->base() . '/' . $route : $this->base();
    }

    public function get(string $path = '', bool $isFullBase = true): string
    {
        $urlManager = new UrlManager();

        if (str_starts_with($path, '^')) {
            $path = Str::firstDelete($path, '^');
            $isFullBase = false;
        }

        if (str_starts_with($path, '~')) {
            $path = Str::firstDelete($path, '~');
            $urlManager->setBasePath($this->site($isFullBase));
        } else {
            $urlManager->setBasePath($this->app($isFullBase));
        }
        return $urlManager->get($path);
    }


    public function loc(string $path = '', bool $isFullBase = true): string
    {
        $urlManager = new UrlManager();

        if (str_starts_with($path, '^')) {
            $path = Str::firstDelete($path, '^');
            $isFullBase = false;
        }

        $path = Str::firstDelete($path, $this->basePath);
        $path = Str::firstDelete($path, '/');
        $site = $this->site($isFullBase);
        $site = Str::lastDelete($site, '/');
        $basePath = $site . '/' . $path;
        $urlManager->setBasePath($basePath);

        return $urlManager->get();
    }

    public function path(string $path = '', bool $isFullBase = true): string
    {
        if (str_starts_with($path, $this->basePath)) {
            return $this->loc($path, $isFullBase);
        }
        $urlManager = new UrlManager();

        if (str_starts_with($path, '^')) {
            $path = Str::firstDelete($path, '^');
            $isFullBase = false;
        }

        if (str_starts_with($path, '~')) {
            $path = Str::firstDelete($path, '~');
            $urlManager->setBasePath($this->site($isFullBase));
        } else {
            $basePath = Str::firstDelete($this->app->path(), $this->basePath);
            $basePath = Str::firstDelete($basePath, '/');
            $site = $this->site($isFullBase);
            $site = Str::lastDelete($site, '/');
            $basePath = $site . '/' . $basePath;
            $urlManager->setBasePath($basePath);
        }
        return $urlManager->get($path);
    }

    public function check($link, $default = null)
    {
        if (!empty($link) && $this->existsFile($link)) {
            return $link;
        } else {
            return $default;
        }
    }

    public function existsFile($link): bool
    {
        if (empty($link)) return false;

        if (Str::firstHas($link, $this->site())) {
            $link = Str::firstDelete($link, $this->site());
        }

        $pathManager = new PathManager($this->basePath);
        $file = $pathManager->get($link);

        if (is_file($file)) {
            return true;
        }
        return false;
    }

    /**
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Get request path without app base path
     * 
     * @return string
     */
    public function pathWithoutBase(): string
    {
        $path = $this->request->getPathInfo();
        
        // Remove app base path if exists
        $basePath = $this->app->pathRoute();
        if (!empty($basePath) && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        
        // Ensure path starts with /
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        return $path;
    }

    public function referer()
    {
        return $this->request->headers->get('referer');
    }

    public function current()
    {
        return $this->request->getUri();
    }

    public function isQueryRoute(): bool
    {
        return $this->request->isQueryRoute();
    }

    public function queryRoute(string $path = '', bool $isFullBase = true): string
    {
        return QueryRouteResolver::buildUrl($this->site($isFullBase), $path);
    }

    public function queryRouteForApp(string $path, ?string $package = null, bool $isFullBase = true): string
    {
        $package = $package ?? $this->app->package();

        return QueryRouteResolver::buildUrl($this->app($isFullBase), $path);
    }

    /**
     * Get all URLs for a specific app
     * 
     * @param string $packageName The package name of the app
     * @return array Array of URLs for the app
     */
    public function getAppUrls(string $packageName): array
    {
        $routes = $this->appRouter->get();
        $appUrls = [];
        
        foreach ($routes as $path => $app) {
            if ($app === $packageName) {
                $appUrls[] = $this->site() . $path;
            }
        }
        
        return $appUrls;
    }
}