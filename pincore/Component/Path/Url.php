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


use Pinoox\Component\Dir;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\App;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Component\Path\Manager\UrlManager;

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

    public function clientIps(): array
    {
        return $this->request->getClientIps();
    }

    public function base(): string
    {
        return $this->request->getBaseUrl();
    }

    public function params(): string
    {
        return $this->request->getPathInfo();
    }

    public function parameters(): array
    {
        return array_filter(explode('/', $this->params()));
    }

    public function site(bool $isFullBase = true): string
    {
        if ($isFullBase)
            return $this->request->getUriForPath('');
        else
            return $this->base();
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
}