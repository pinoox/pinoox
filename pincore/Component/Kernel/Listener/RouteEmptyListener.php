<?php

namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Http\Request;
use Pinoox\Portal\App\App as AppPortal;
use Pinoox\Portal\Lang;
use Pinoox\Portal\Path;
use Pinoox\Portal\View;
use Pinoox\Support\SystemConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pinoox\Component\Http\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class RouteEmptyListener implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();

        if ($e instanceof NotFoundHttpException && ($e->getPrevious() instanceof NoConfigurationException || $e->getPrevious() instanceof ResourceNotFoundException)) {
            $event->setResponse($this->createWelcomeResponse($event->getRequest()));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -64],
        ];
    }

    private function createWelcomeResponse(Request $request): Response
    {
        $app = AppPortal::___();
        $package = (string) ($app->package() ?? '');
        $routeFiles = $app->get('router.routes', ['routes/web.php']);

        if (!is_array($routeFiles)) {
            $routeFiles = [$routeFiles];
        }

        $routeFiles = array_values(array_filter($routeFiles));
        $primaryRouteFile = $routeFiles[0] ?? 'routes/web.php';
        $locales = $this->availableLocales();
        $locale = $this->resolveLocale($request, $locales);

        Lang::setLocale($locale);

        View::changeTheme('no-route', Path::get('~pincore/resource/views/no-route/'));

        return new Response(View::render('home', [
            'package' => $package,
            'appName' => (string) $app->get('name', 'App'),
            'appPath' => $package !== '' ? $app->path() : '',
            'routeFiles' => $routeFiles,
            'primaryRouteFile' => $primaryRouteFile,
            'locale' => $locale,
            'dir' => (string) Lang::get('no-route.meta.dir', [], $locale, false),
            'locales' => $locales,
        ]));
    }

    private function availableLocales(): array
    {
        $locales = [];
        $pattern = SystemConfig::path('platform_lang') . '/*/no-route.lang.php';

        foreach (glob($pattern) ?: [] as $file) {
            $code = basename(dirname($file));

            if (!preg_match('/^[a-z]{2}$/', $code)) {
                continue;
            }

            $data = require $file;
            $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
            $locales[$code] = (string) ($meta['name'] ?? strtoupper($code));
        }

        if ($locales === []) {
            return ['en' => 'English'];
        }

        ksort($locales);

        return $locales;
    }

    private function resolveLocale(Request $request, array $locales): string
    {
        $available = array_keys($locales);
        $requested = $request->query->get('lang');

        if (is_string($requested) && in_array($requested, $available, true)) {
            return $requested;
        }

        $current = Lang::getLocale();

        if (in_array($current, $available, true)) {
            return $current;
        }

        return $available[0] ?? 'en';
    }
}

