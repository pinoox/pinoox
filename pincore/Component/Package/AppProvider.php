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
use Pinoox\Component\Event\EventDispatcher;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Kernel\Terminal;
use Pinoox\Portal\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AppProvider
{
    private array $lock = [];

    public function __construct(
        private readonly App    $app,
        public Kernel           $httpKernel,
        public Terminal         $terminal,
        public SessionInterface $session,
        public EventDispatcher  $eventDispatcher,
    )
    {
    }

    private function loader(): void
    {
        $appLoaders = $this->app->get('loader');
        if (empty($appLoaders))
            return;
        $classMap = [];
        foreach ($appLoaders as $classname => $path) {
            if (Str::firstHas($classname, '@')) {
                require_once $this->app->path($path);
            } else {
                $classMap[$classname] = $path;
            }
        }

        $this->getClassLoader()->addClassMap($classMap);
    }

    private function events(): void
    {
        $events = $this->app->get('event');
        if (empty($events))
            return;

        foreach ($events as $event => $listener) {
            if (is_string($listener))
                $listener = $this->app->alias($listener, $listener);

            if (is_subclass_of ($listener,EventSubscriberInterface::class)) {
                $this->eventDispatcher->addSubscriber(new $listener());
            } else if(is_string($event)) {
                $this->eventDispatcher->addListener($event, $listener);
            }
        }
    }

    private function resolveSession()
    {
        $sessionConf = $this->app->get('session');
        $startSession = $sessionConf === true || $sessionConf === 'start';

        if (is_array($sessionConf)) {
            $session = class_exists($sessionConf[0]) ? new $sessionConf[0]() : $sessionConf;
            $startSession = isset($sessionConf[1]) && $sessionConf[1] === 'start';
        } elseif (is_string($sessionConf)) {
            $session = class_exists($sessionConf) ? new $sessionConf() : $sessionConf;
        } else {
            $session = $sessionConf;
        }

        $this->getRequest()->setSession($session instanceof SessionInterface ? $session : $this->session);

        if ($startSession && $this->getRequest()->hasSession()) {
            $this->getRequest()->getSession()->start();
        }
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

    /**
     * @throws Exception
     */
    public function prerequisite(): void
    {
        if (!$this->isLock()) {
            $this->lock();
            $this->loadComposer($this->app->path());
            $this->loader();
            $this->events();
            $this->resolveSession();
        }
    }

    private function loadComposer($dir): ?ClassLoader
    {
        $composer = null;
        if (is_file($file = $dir . '/vendor/autoload.php'))
            $composer = require $file;

        return $composer;
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
        return $this->app->meeting($package, function () use ($request) {
            $request = !empty($request) ? $request : $this->getRequest();
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