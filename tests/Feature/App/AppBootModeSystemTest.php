<?php

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Package\AppRouter;
use Pinoox\Component\Router\Action\ActionRegistry;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;

beforeEach(function () {
    pinooxBoot();
    AppBootstrap::resetState();
    ActionRegistry::reset();
});

afterEach(function () {
    AppBootstrap::resetState();
});

it('resolves route-only apps when the URL matches and skips boot.php when boot is disabled', function () {
    $host = appBootModePackage('route');

    fakeApp($host, [
        'app.php' => appBootModeManifest($host, [
            'boot' => false,
            'router' => ['routes' => ['routes/web.php']],
        ]),
        'boot.php' => appBootModeBootFile('must-not-run'),
        'routes/web.php' => appBootModeWebRoute(),
    ]);

    $engine = AppEngine::___();
    $request = Request::create('http://localhost/' . $host, 'GET');
    $router = new AppRouter(new AppBootModeRouterConfig(['/' . $host => $host]), $engine, $request);

    expect($router->find()->getPackageName())->toBe($host)
        ->and(AppBootstrap::booted($host))->toBeFalse();

    AppBootstrap::markKernelReady();
    AppBootstrap::ensure($host, true);

    expect(AppBootstrap::booted($host))->toBeTrue()
        ->and(appBootModeMarker($host))->toBeNull();

    deleteFakeApp($host);
});

it('boots boot-global apps on every request before the active route app', function () {
    $global = appBootModePackage('global');
    $host = appBootModePackage('host');

    fakeApp($global, [
        'app.php' => appBootModeManifest($global, [
            'boot-global' => true,
            'router' => ['routes' => []],
        ]),
        'boot.php' => appBootModeBootFile('global-booted'),
    ]);

    fakeApp($host, [
        'app.php' => appBootModeManifest($host, [
            'router' => ['routes' => ['routes/web.php']],
        ]),
        'boot.php' => appBootModeBootFile('host-booted'),
        'routes/web.php' => appBootModeWebRoute(),
    ]);

    AppBootstrap::resetState();
    AppBootstrap::markKernelReady();
    AppBootstrap::bootGlobalApps(false);

    expect(AppBootstrap::booted($global))->toBeTrue()
        ->and(appBootModeMarker($global))->toBe('global-booted')
        ->and(AppBootstrap::booted($host))->toBeFalse();

    AppBootstrap::ensure($host, false);

    expect(AppBootstrap::booted($host))->toBeTrue()
        ->and(appBootModeMarker($host))->toBe('host-booted');

    deleteFakeApp($global);
    deleteFakeApp($host);
});

it('boots both boot.php and route files when the matched app has both', function () {
    $package = appBootModePackage('both');

    fakeApp($package, [
        'app.php' => appBootModeManifest($package, [
            'router' => ['routes' => ['routes/web.php', 'routes/actions.php']],
        ]),
        'boot.php' => appBootModeBootFile('both-booted'),
        'routes/web.php' => appBootModeWebRoute(),
        'routes/actions.php' => appBootModeActionsRoute($package),
    ]);

    AppBootstrap::markKernelReady();
    AppBootstrap::ensure($package, false);

    expect(AppBootstrap::booted($package))->toBeTrue()
        ->and(appBootModeMarker($package))->toBe('both-booted');

    inApp($package, function () use ($package) {
        expect(AppEngine::___()->router($package)->count())->toBeGreaterThan(0);
    });

    deleteFakeApp($package);
});

it('boots extenders before the host app without a router mapping for the extender', function () {
    $host = appBootModePackage('host');
    $plugin = appBootModePackage('plugin');

    fakeApp($host, [
        'app.php' => appBootModeManifest($host, [
            'router' => ['routes' => ['routes/web.php']],
        ]),
        'boot.php' => appBootModeBootFile('host-booted'),
        'routes/web.php' => appBootModeWebRoute(),
    ]);

    fakeApp($plugin, [
        'app.php' => appBootModeManifest($plugin, [
            'extends' => [$host],
            'router' => ['routes' => []],
        ]),
        'boot.php' => appBootModeBootFile('plugin-booted'),
    ]);

    AppBootstrap::resetState();
    AppBootstrap::markKernelReady();
    AppBootstrap::ensure($host, false);

    expect(AppBootstrap::booted($plugin))->toBeTrue()
        ->and(appBootModeMarker($plugin))->toBe('plugin-booted')
        ->and(AppBootstrap::booted($host))->toBeTrue()
        ->and(appBootModeMarker($host))->toBe('host-booted');

    deleteFakeApp($host);
    deleteFakeApp($plugin);
});

it('lists boot-global packages from stable enabled apps only', function () {
    $enabled = appBootModePackage('enabled');
    $disabled = appBootModePackage('disabled');

    fakeApp($enabled, [
        'app.php' => appBootModeManifest($enabled, ['boot-global' => true, 'enable' => true]),
    ]);

    fakeApp($disabled, [
        'app.php' => appBootModeManifest($disabled, ['boot-global' => true, 'enable' => false]),
    ]);

    AppBootstrap::resetState();

    expect(AppBootstrap::globalBootPackages())->toContain($enabled)
        ->and(AppBootstrap::globalBootPackages())->not->toContain($disabled);

    deleteFakeApp($enabled);
    deleteFakeApp($disabled);
});

it('includes boot.global in the default boot pipeline stages', function () {
    $package = appBootModePackage('pipe');

    fakeApp($package, [
        'app.php' => appBootModeManifest($package, ['boot-global' => true]),
    ]);

    inApp($package, function () {
        expect(AppProvider::___()->bootStages())->toContain('boot.global', 'app.boot');
    });

    deleteFakeApp($package);
});

function appBootModePackage(string $role): string
{
    return 'com_boot_' . $role . '_' . bin2hex(random_bytes(4));
}

/**
 * @param array<string, mixed> $extra
 */
function appBootModeManifest(string $package, array $extra = []): string
{
    $config = array_replace([
        'package' => $package,
        'name' => $package,
        'enable' => true,
        'router' => ['routes' => []],
    ], $extra);

    return '<?php return ' . var_export($config, true) . ';';
}

function appBootModeBootFile(string $marker): string
{
    $marker = addslashes($marker);

    return <<<PHP
<?php

use Pinoox\Component\AppEvent\AppRegister;

return function (AppRegister \$register): void {
    file_put_contents(__DIR__ . '/.boot-marker', '{$marker}');
};
PHP;
}

function appBootModeWebRoute(): string
{
    return <<<'PHP'
<?php

use function Pinoox\Router\get;

get('/ping', fn () => response('pong'));
PHP;
}

function appBootModeActionsRoute(string $package): string
{
    $action = 'boot_' . substr(md5($package), 0, 12);

    return <<<PHP
<?php

use function Pinoox\Router\action;

action('{$action}', fn () => response('home'));
PHP;
}

function appBootModeMarker(string $package): ?string
{
    $file = appPath($package) . '/.boot-marker';

    return is_file($file) ? trim((string) file_get_contents($file)) : null;
}

class AppBootModeRouterConfig implements \Pinoox\Component\Store\Config\ConfigInterface
{
    public function __construct(private array $data = [])
    {
    }

    public function get(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function add(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function save(): static
    {
        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->data = is_array($data) ? $data : [];

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->data[$key]);

        return $this;
    }
}

