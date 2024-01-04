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


use Composer\Autoload\ClassLoader;
use Exception;
use Pinoox\Component\Helpers\HelperString;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Kernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppProvider
{
    private array $lock = [];
    private bool $isLockMain = false;

    public function __construct(
        private readonly App         $app,
        private readonly ClassLoader $classLoader,
        private readonly Kernel      $httpKernel
    )
    {
    }

    private function loader()
    {
        $appLoaders = $this->app->get('loader');
        $classMap = [];
        foreach ($appLoaders as $classname => $path) {
            if (HelperString::firstHas($classname, '@')) {
                require_once $this->app->path($path);
            } else {
                $classMap[$classname] = $path;
            }
        }

        $this->classLoader->addClassMap($classMap);
    }

    private function isLock(): bool
    {
        return isset($this->lock[$this->app->package()]) ? $this->lock[$this->app->package()] : false;
    }

    public function prerequisite(): void
    {
        if (!$this->isLock()) {
            $this->lock[$this->app->package()] = true;
            $this->loader();
        }
    }

    public function getRequest(): Request
    {
        return $this->app->getAppRouter()->getRequest();
    }

    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if (!$this->isLockMain) {
            $response = $this->handle($this->getRequest());
            $response->send();
            $this->terminate($this->getRequest(), $response);
            $this->isLockMain = true;
        }
    }

    /**
     * @throws Exception
     */
    public function meetingHandle(string $package, string $path, ?Request $request = null, array $attributes = [])
    {
        return $this->app->meeting($package, function () use ($request) {
            $request = !empty($request) ? $request : $this->request;
            $subRequest = $request->duplicate();
            if (empty($attributes))
                $attributes = $this->app->router()->matchRequest($request);
            $subRequest->attributes->add($attributes);
            return $this->handle($subRequest, 3);
        }, $path);
    }

    /**
     * @throws Exception
     */
    public function handle(?Request $request = null, int $type = HttpKernelInterface::MAIN_REQUEST): Response
    {
        $this->prerequisite();
        $request = !empty($request) ? $request : $this->request;
        $response = $this->getKernel()->handle($request, $type);
        return new Response($response->getContent(), $response->getStatusCode(), $response->headers->all());
    }

    /**
     * @throws Exception
     */
    public function handleByRoute(string $package, ?Request $request = null)
    {
        $request = !empty($request) ? $request : $this->request;
        $route = $request->attributes->get('_router');
        $path = '/';
        if (!empty($route)) {
            $path = $route->get()->compile()->getStaticPrefix();
        }
        return $this->meetingHandle($package, $path, $request);
    }

    public function getKernel(): Kernel
    {
        return $this->httpKernel;
    }

    public function terminate(Request $request, \Pinoox\Component\Http\Response $response): void
    {
        $this->getKernel()->terminate($request, $response);
    }
}