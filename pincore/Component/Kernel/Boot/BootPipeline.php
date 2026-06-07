<?php

namespace Pinoox\Component\Kernel\Boot;

use Closure;
use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Container\ServiceContainerBootstrap;
use Pinoox\Component\Kernel\SessionStarter;
use Pinoox\Component\User\AuthConfig;
use Pinoox\PinDoc\Api\AppApiServiceProvider;
use Pinoox\PinDoc\GraphQL\GraphQLServiceProvider;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Database\DB;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BootPipeline
{
    /** @var list<array{label: string, handler: Closure}> */
    private array $stages = [];

    public function __construct(
        private readonly BootContext $context,
        private readonly object $provider,
    ) {
    }

    public static function for(object $provider, BootContext $context): self
    {
        return (new self($context, $provider))
            ->registerDefaults();
    }

    /**
     * @return list<string>
     */
    public function stageNames(): array
    {
        return array_column($this->stages, 'label');
    }

    public function run(?string $until = null): void
    {
        foreach ($this->stages as $stage) {
            ($stage['handler'])();

            if ($until !== null && $stage['label'] === $until) {
                break;
            }
        }
    }

    public function add(string $label, Closure $handler, ?string $after = null): self
    {
        $entry = ['label' => $label, 'handler' => $handler];

        if ($after === null) {
            $this->stages[] = $entry;

            return $this;
        }

        $index = $this->indexOf($after);
        if ($index === null) {
            $this->stages[] = $entry;

            return $this;
        }

        array_splice($this->stages, $index + 1, 0, [$entry]);

        return $this;
    }

    private function registerDefaults(): self
    {
        return $this
            ->add('composer', fn () => $this->loadComposer())
            ->add('loader', fn () => $this->loadAppLoader())
            ->add('boot.global', fn () => $this->bootGlobalApps())
            ->add('app.boot', fn () => $this->bootApp())
            ->add('container', fn () => ServiceContainerBootstrap::boot($this->context->package()))
            ->add('events', fn () => $this->registerLegacyEvents())
            ->add('database', fn () => $this->registerDatabase())
            ->add('api', fn () => $this->registerApi())
            ->add('session', fn () => $this->resolveSession());
    }

    private function loadComposer(): void
    {
        $dir = $this->context->path();
        if (is_file($file = $dir . '/vendor/autoload.php')) {
            require $file;
        }
    }

    private function loadAppLoader(): void
    {
        $loaders = $this->context->app->get('loader');
        if (empty($loaders) || !is_array($loaders)) {
            return;
        }

        $classMap = [];
        foreach ($loaders as $classname => $path) {
            if (Str::firstHas($classname, '@')) {
                require_once $this->context->path($path);
                continue;
            }

            $classMap[$classname] = $path;
        }

        if ($classMap !== []) {
            $this->context->classLoader->addClassMap($classMap);
        }
    }

    private function bootGlobalApps(): void
    {
        AppBootstrap::markKernelReady();
        AppBootstrap::bootGlobalApps(true);
    }

    private function bootApp(): void
    {
        $package = $this->context->package();
        if ($package === '') {
            return;
        }

        AppBootstrap::markKernelReady();
        AppBootstrap::ensure($package, true);
    }

    private function registerLegacyEvents(): void
    {
        $events = $this->context->app->get('event');
        if (empty($events) || !is_array($events)) {
            return;
        }

        $dispatcher = $this->provider->eventDispatcher;

        foreach ($events as $event => $listener) {
            if (is_string($listener)) {
                $listener = $this->context->app->alias($listener, $listener);
            }

            if (is_subclass_of($listener, EventSubscriberInterface::class)) {
                $dispatcher->addSubscriber(new $listener());
            } elseif (is_string($event)) {
                $dispatcher->addListener($event, $listener);
            }
        }
    }

    private function registerDatabase(): void
    {
        $package = $this->context->package();
        if ($package !== '') {
            DB::registerPackageConnections($package);
        }
    }

    private function registerApi(): void
    {
        AppApiServiceProvider::register();
        GraphQLServiceProvider::register();
    }

    private function resolveSession(): void
    {
        $authMode = strtolower((string) ($this->context->app->get('auth.mode') ?? ''));
        if ($authMode === AuthConfig::MODE_SESSION) {
            return;
        }

        $sessionConf = $this->context->app->get('session');
        $startSession = $sessionConf === true || $sessionConf === 'start';

        if (is_array($sessionConf)) {
            $session = class_exists($sessionConf[0]) ? new $sessionConf[0]() : $sessionConf;
            $startSession = isset($sessionConf[1]) && $sessionConf[1] === 'start';
        } elseif (is_string($sessionConf)) {
            $session = class_exists($sessionConf) ? new $sessionConf() : $sessionConf;
        } else {
            $session = $sessionConf;
        }

        $request = $this->provider->getRequest();
        $request->setSession($session instanceof SessionInterface ? $session : $this->provider->session);

        if ($startSession && $request->hasSession()) {
            SessionStarter::start($request);
        }
    }

    private function indexOf(string $label): ?int
    {
        foreach ($this->stages as $index => $stage) {
            if ($stage['label'] === $label) {
                return $index;
            }
        }

        return null;
    }
}

