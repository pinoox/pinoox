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

namespace Pinoox\Component\Package;

use Closure;
use Composer\Autoload\ClassLoader;
use Exception;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Lang\Lang;
use Pinoox\Component\Router\Collection;
use Pinoox\Component\Router\RouteCollection;
use Pinoox\Component\Router\Router;
use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Component\Package\Engine\AppEngine;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request as RequestSymfony;

class App implements UrlMatcherInterface, RequestMatcherInterface
{
    private AppLayer $appLayer;

    public function __construct(
        private readonly AppRouter  $appRouter,
        public readonly AppEngine   $appEngine,
        public RequestContext       $context,
        public readonly ClassLoader $classLoader,
    )
    {
        $this->appLayer = $this->appRouter->find();
    }

    /**
     * Get the package name of the current application
     *
     * @return string|null
     */
    public function package(): ?string
    {
        return $this->appLayer?->getPackageName();
    }

    /**
     * Get the package name of the current application
     *
     * @return string|null
     */
    public function route(): ?string
    {
        return $this->appLayer?->getPath();
    }

    /**
     * Get App stake
     *
     * @return AppLayer
     */
    public function current(): AppLayer
    {
        return $this->appLayer;
    }

    /**
     * Set App stake
     *
     * @param AppLayer $appLayer
     */
    public function setLayer(AppLayer $appLayer): void
    {
        $this->appLayer->setPath($appLayer->getPath());
        $this->appLayer->setPackageName($appLayer->getPackageName());
    }

    /**
     * @param string $packageName
     * @param Closure $closure
     * @param string $path
     * @return mixed
     * @throws Exception
     */
    public function meeting(string $packageName, Closure $closure, string $path = ''): mixed
    {
        if (!$this->exists($packageName))
            throw new Exception('package `' . $packageName . '` not found!');

        $mainLayer = new AppLayer($this->appLayer->getPath(), $this->appLayer->getPackageName());

        $this->setLayer(new AppLayer($path, $packageName));
        if (!is_callable($closure))
            throw new Exception('the value must be of function type');

        $result = $closure();

        $this->setLayer($mainLayer);

        return $result;
    }

    /**
     * App exists
     * @param string $packageName
     * @return bool
     */
    public function exists(string $packageName): bool
    {
        return $this->appEngine->exists($packageName);
    }

    /**
     * Check App for use has stable
     *
     * @param string $packageName
     * @return bool
     */
    public function stable(string $packageName): bool
    {
        $enable = false;

        if ($this->exists($packageName)) {
            try {
                $enable = (bool)$this->get('enable');
            } catch (Exception $e) {
            }
        }

        return $enable === true;
    }

    /**
     * Get data from config current app
     *
     * @param string|null $value
     * @return mixed
     */
    public function get(?string $value = null): mixed
    {
        $packageName = $this->appLayer?->getPackageName();

        if (empty($packageName))
            return null;

        try {
            return $this->config()->get($value);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Set data in config current app
     *
     * @param string $key
     * @param mixed $value
     * @return ConfigInterface|null
     * @throws Exception
     */
    public function set(string $key, mixed $value): ?ConfigInterface
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        $this->config()->set($key, $value);
        return $this->config();
    }

    /**
     * Set data in config current app
     *
     * @param string $key
     * @param mixed $value
     * @return ConfigInterface|null
     * @throws Exception
     */
    public function add(string $key, mixed $value): ?ConfigInterface
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        $this->config()->add($key, $value);
        return $this->config();
    }

    /**
     * Set data in config current app
     *
     * @return ConfigInterface|null
     * @throws Exception
     */
    public function save(): ?ConfigInterface
    {
        $packageName = $this->appLayer?->getPackageName();
        if (empty($packageName))
            return null;

        return $this->config()->save();
    }

    public function manager(): AppManager
    {
        return $this->appEngine->manager($this->package());
    }

    /**
     * @throws Exception
     */
    public function config(): ConfigInterface
    {
        return $this->appEngine->config($this->package());
    }

    /**
     * @throws Exception
     */
    public function lang(): Lang
    {
        return $this->appEngine->lang($this->package());
    }

    /**
     * @throws Exception
     */
    public function path(string $path = ''): string
    {
        return $this->appEngine->path($this->package(), $path);
    }

    public function router(): Router
    {
        return $this->appEngine->router($this->package(), $this->route());
    }

    public function routeCollection(): RouteCollection
    {
        return $this->router()->getCollection()->routes;
    }

    public function getUrlMatcher(?RequestContext $context = null): UrlMatcherInterface|RequestMatcherInterface
    {
        return $this->router()->getUrlMatcher($context);
    }

    public function match(string $pathinfo, ?Request $request = null): array
    {
        return $this->router()->match($pathinfo, $request);
    }

    public function matchRequest(Request|RequestSymfony $request): array
    {
        return $this->router()->matchRequest($request);

    }

    public function collection(): Collection
    {
        return $this->router()->getCollection();
    }

    /**
     * @return AppRouter
     */
    public function getAppRouter(): AppRouter
    {
        return $this->appRouter;
    }

    /**
     * @return RequestContext
     */
    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * @param RequestContext $context
     */
    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getRequest(): Request
    {
        return $this->getAppRouter()->getRequest();
    }

    public function addPackage(string $packageName, string $dir): void
    {
        $this->autoloader($packageName, $dir);
        $this->appEngine->add($packageName, $dir);
    }

    private function autoloader($packageName, $dir): void
    {
        $namespace = 'App\\' . $packageName . '\\';
        $this->classLoader->addPsr4($namespace, $dir);
        spl_autoload_register(function ($class) use ($namespace, $dir) {
            if (str_starts_with($class, $namespace)) {
                $class = str_replace($namespace, '', $class);
                $filename = str_replace('\\', '/', $class) . '.php';
                $filePath = $dir . '/' . $filename;
                if (file_exists($filePath)) {
                    require $filePath;
                }
            }
        });
    }
}

