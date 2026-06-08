<?php

namespace Pinoox\Component\Package;

use Composer\Autoload\ClassLoader;
use Exception;
use Pinoox\Component\Event\EventDispatcher;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Boot\BootContext;
use Pinoox\Component\Kernel\Boot\BootPipeline;
use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Kernel\Terminal;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppProvider
{
    private array $lock = [];
    /** @var array<string, BootPipeline> */
    private array $pipelines = [];

    public function __construct(
        private readonly App    $app,
        public Kernel           $httpKernel,
        public Terminal         $terminal,
        public SessionInterface $session,
        public EventDispatcher  $eventDispatcher,
    )
    {
    }

    private function getClassLoader(): ClassLoader
    {
        return $this->app->classLoader;
    }

    private function isLock(): bool
    {
        return isset($this->lock[$this->app->package()]) ? $this->lock[$this->app->package()] : false;
    }

    private function lock(): void
    {
        $this->lock[$this->app->package()] = true;
    }

    private function bootContext(): BootContext
    {
        return new BootContext($this->app, $this->getClassLoader());
    }

    public function pipeline(): BootPipeline
    {
        $package = $this->app->package();
        if (!isset($this->pipelines[$package])) {
            $this->pipelines[$package] = BootPipeline::for($this, $this->bootContext());
        }
        return $this->pipelines[$package];
    }
    /**
     * @return list<string>
     */
    public function bootStages(): array
    {
        return $this->pipeline()->stageNames();
    }
    /**
     * @throws Exception
     */
    public function prerequisite(): void
    {
        if (!$this->isLock()) {
            $this->lock();
            $this->pipeline()->run();
        }
    }

    public function getRequest(): Request
    {
        return $this->app->getRequest();
    }

    public function getApp(): App
    {
        return $this->app;
    }
    /**
     * @throws Exception
     */
    public function run(string $package = '', string $path = '/'): void
    {
        if (!empty($package)) {
            $this->app->meeting($package, function () {
                $this->getRequest()->attributes = new ParameterBag();
                $this->run();
            }, $path);
        } else {
            $response = $this->handle($this->getRequest());
            $response->send();
            $this->terminate($this->getRequest(), $response);
        }
    }
    /**
     * @throws Exception
     */
    public function meetingHandle(string $package, string $path, ?Request $request = null, array $attributes = [])
    {
        return $this->app->meeting($package, function () use ($request, $attributes) {
            $request = !empty($request) ? $request : $this->getRequest();
            $subRequest = $request->duplicate();
            $subRequest->session = null;
            $subRequest->attributes = new ParameterBag();

            if ($attributes === []) {
                $attributes = $this->app->router()->matchRequest($subRequest);
            }

            $subRequest->attributes->add($attributes);

            return $this->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }, $path);
    }
    /**
     * @throws Exception
     */
    public function handle(?Request $request = null, int $type = HttpKernelInterface::MAIN_REQUEST): Response
    {
        $this->prerequisite();
        $request = !empty($request) ? $request : $this->getRequest();
        $response = $this->getKernel()->handle($request, $type);
        return new Response($response->getContent(), $response->getStatusCode(), $response->headers->all());
    }
    /**
     * @throws Exception
     */
    public function handleByRoute(string $package, ?Request $request = null)
    {
        $request = !empty($request) ? $request : $this->getRequest();
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
    /**
     * @throws Exception
     */
    public function boot(string $package = ''): void
    {
        if (!empty($package)) {
            $dir = dirname(get_included_files()[0]);
            $this->app->addPackage($package, $dir);
            $this->app->setLayer(new AppLayer('/', $package));
        }
        if (empty($this->getRequest()->getHost())) {
            $this->terminal->run();
        } else {
            $this->run();
        }
    }
}

