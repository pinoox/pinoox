<?php

namespace Pinoox\Component\Test;

use Closure;
use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\AppEvent\AppRouteRegistry;
use Pinoox\Component\Package\AppEnv\AppEnvBridge;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Component\Http\Response;
use Pinoox\PinDoc\Api\AppApiServiceProvider;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Config;
use Pinoox\Support\SystemConfig;

class AppTestKit
{
    private static bool $booted = false;

    private static ?string $activePackage = null;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        if (!defined('PINOOX_START')) {
            define('PINOOX_START', microtime(true));
        }

        // Warm portals (Env, DB, …) without AppProvider::boot() — that runs Terminal in CLI.
        AppProvider::___();
        Config::name('~pinoox')->set('mode', 'test');

        $request = App::getRequest();
        if ($request->getHost() === '' || $request->getHost() === null) {
            $request->headers->set('HOST', 'pinoox.test');
            $request->server->set('HTTP_HOST', 'pinoox.test');
        }

        self::$booted = true;
    }

    public static function setPackage(?string $package): void
    {
        self::$activePackage = $package;
    }

    public static function package(?string $package = null): string
    {
        if ($package !== null) {
            return $package;
        }

        if (self::$activePackage !== null) {
            return self::$activePackage;
        }

        $detected = self::detectPackageFromPath();

        if ($detected !== null) {
            return $detected;
        }

        throw new \RuntimeException('App package not set. Pass $package or call appPackage() / AppTestKit::setPackage().');
    }

    public static function detectPackageFromPath(?string $file = null): ?string
    {
        $file ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? '';

        if (preg_match('#/apps/([^/]+)/tests/#', str_replace('\\', '/', $file), $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function inApp(string $package, Closure $callback, string $path = '/'): mixed
    {
        self::boot();

        return App::meeting($package, $callback, $path);
    }

    public static function path(string $package, string $subPath = ''): string
    {
        self::boot();

        if (AppEngine::exists($package)) {
            return rtrim(AppEngine::path($package, $subPath), '/');
        }

        $base = SystemConfig::path('apps') . '/' . $package;

        return $subPath === '' ? $base : $base . '/' . ltrim($subPath, '/');
    }

    /**
     * @param array<string, string> $files relative path => contents
     */
    public static function fakeApp(string $package, array $files = []): string
    {
        self::boot();

        $dir = self::path($package);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!isset($files['app.php'])) {
            $files['app.php'] = "<?php\n\nreturn ['package' => '{$package}', 'enable' => true, 'name' => '{$package}'];\n";
        }

        foreach ($files as $file => $content) {
            $target = $dir . '/' . ltrim(str_replace('\\', '/', $file), '/');
            $folder = dirname($target);

            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            file_put_contents($target, $content);
        }

        AppEnvBridge::reset();
        AppEngine::__rebuild();

        return $dir;
    }

    public static function deleteFakeApp(string $package): void
    {
        self::deleteDirectory(self::path($package));
        self::deleteDirectory(SystemConfig::resolvePath('~pinker/apps/' . $package));
        self::deleteDirectory(SystemConfig::resolvePath('~pinker/state/apps/' . $package));
        AppEnvBridge::reset();
        AppEngine::__rebuild();
    }

    /**
     * Remove transient apps and fixture files left by feature tests.
     *
     * Safe to call before/after each test — system apps are never deleted.
     */
    public static function cleanupTransientArtifacts(bool $rebuild = true): void
    {
        self::boot();

        $appsRoot = rtrim(str_replace('\\', '/', SystemConfig::path('apps')), '/');

        foreach (glob($appsRoot . '/com_test_*') ?: [] as $path) {
            if (is_dir($path)) {
                self::deleteDirectory($path);
            }
        }

        foreach (glob($appsRoot . '/com_boot_*') ?: [] as $path) {
            if (is_dir($path)) {
                self::deleteDirectory($path);
            }
        }

        $pinkerApps = rtrim(str_replace('\\', '/', SystemConfig::path('pinker') . '/apps'), '/');
        foreach (glob($pinkerApps . '/com_test_*') ?: [] as $path) {
            if (is_dir($path)) {
                self::deleteDirectory($path);
            }
        }
        foreach (glob($pinkerApps . '/com_boot_*') ?: [] as $path) {
            if (is_dir($path)) {
                self::deleteDirectory($path);
            }
        }

        $pinkerState = rtrim(str_replace('\\', '/', SystemConfig::path('pinker') . '/state/apps'), '/');
        if (is_dir($pinkerState)) {
            foreach (glob($pinkerState . '/com_test_*') ?: [] as $path) {
                if (is_dir($path)) {
                    self::deleteDirectory($path);
                }
            }
            foreach (glob($pinkerState . '/com_boot_*') ?: [] as $path) {
                if (is_dir($path)) {
                    self::deleteDirectory($path);
                }
            }
        }

        self::cleanFixtureTree(self::projectRoot() . '/tests/Fixtures');
        self::cleanFixtureTree(self::projectRoot() . '/tests/Fixtures/sandbox');
        @unlink(self::projectRoot() . '/tests/Fixtures/schedule-marker.txt');
        @unlink(self::projectRoot() . '/tests/Fixtures/app_registry.config.php');

        self::cleanupWebServerFixCaches($pinkerApps);

        ActionRegistry::reset();
        AppRouteRegistry::reset();
        AppBootstrap::resetState();
        AppApiServiceProvider::resetState();

        if ($rebuild) {
            try {
                AppEngine::__rebuild();
                \Pinoox\Portal\Router::__rebuild();
            } catch (\Throwable) {
                // Registry may be mid-test; filesystem cleanup still succeeded.
            }
        }
    }

    private static function cleanFixtureTree(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === '.gitkeep') {
                continue;
            }

            $target = $path . '/' . $entry;
            if (is_dir($target)) {
                self::deleteDirectory($target);
            } else {
                @unlink($target);
            }
        }
    }

    private static function cleanupWebServerFixCaches(string $pinkerAppsRoot): void
    {
        $patterns = [
            $pinkerAppsRoot . '/com_test_*/cache/web_server_fix.php',
            $pinkerAppsRoot . '/com_boot_*/cache/web_server_fix.php',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    public static function request(
        string $method,
        string $uri,
        array $data = [],
        array $query = [],
        array $headers = [],
        ?array $json = null,
    ): Request {
        $server = self::buildServer($method, $uri, $headers);
        $content = $json !== null ? json_encode($json, JSON_THROW_ON_ERROR) : null;
        $parameters = $json === null ? $data : [];

        $request = Request::create($uri, $method, $parameters, [], [], $server, $content);

        foreach ($query as $key => $value) {
            $request->query->set($key, $value);
        }

        if ($json !== null) {
            $request->headers->set('CONTENT_TYPE', 'application/json');
        }

        return $request;
    }

    /**
     * @param array{
     *     data?: array,
     *     query?: array,
     *     headers?: array<string, string>,
     *     json?: array|null,
     *     path?: string
     * } $options
     */
    public static function call(string $package, string $method, string $uri, array $options = []): TestResponse
    {
        self::boot();

        $request = self::request(
            $method,
            $uri,
            $options['data'] ?? [],
            $options['query'] ?? [],
            $options['headers'] ?? [],
            $options['json'] ?? null,
        );

        $routePath = $options['path'] ?? (parse_url($uri, PHP_URL_PATH) ?: '/');

        /** @var Response $response */
        $response = AppProvider::meetingHandle($package, $routePath, $request);

        return new TestResponse($response);
    }

    public static function get(string $package, string $uri, array $query = [], array $headers = []): TestResponse
    {
        return self::call($package, 'GET', $uri, compact('query', 'headers'));
    }

    public static function post(string $package, string $uri, array $data = [], array $headers = []): TestResponse
    {
        return self::call($package, 'POST', $uri, compact('data', 'headers'));
    }

    public static function postJson(string $package, string $uri, array $json = [], array $headers = []): TestResponse
    {
        return self::call($package, 'POST', $uri, ['json' => $json, 'headers' => $headers]);
    }

    public static function projectRoot(): string
    {
        return SystemConfig::rootPath();
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private static function buildServer(string $method, string $uri, array $headers = []): array
    {
        $server = [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
            'SCRIPT_NAME' => '/index.php',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'HTTPS' => 'off',
        ];

        foreach ($headers as $name => $value) {
            $key = str_starts_with(strtoupper($name), 'HTTP_')
                ? strtoupper(str_replace('-', '_', $name))
                : 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        return $server;
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}

