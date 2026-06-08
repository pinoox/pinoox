<?php

use Pinoox\Component\AppEvent\AppBootstrap;
use Pinoox\Component\Package\AppEnv\AppEnvBridge;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Mode;

beforeEach(function () {
    AppTestKit::boot();
    foreach (['com_test_app_env', 'com_test_app_env_alt', 'com_test_app_env_layers'] as $package) {
        deleteAppEnvTestApp($package);
    }
    AppEnvBridge::reset();
    AppBootstrap::resetState();
    AppEngine::__rebuild();
});

afterEach(function () {
    foreach (['com_test_app_env', 'com_test_app_env_alt', 'com_test_app_env_layers'] as $package) {
        deleteAppEnvTestApp($package);
    }
    AppEnvBridge::reset();
    AppBootstrap::resetState();
    AppEngine::__rebuild();
});

it('applies app .env catalog keys onto app config', function () {
    writeAppEnvTestApp('com_test_app_env', [
        'theme' => 'default',
        'lang' => 'en',
    ], "THEME=spark\nMODE=production\nDEBUG=false\nLANG=fa\n");

    $config = AppEngine::config('com_test_app_env');

    expect($config->get('theme'))->toBe('spark')
        ->and($config->get('lang'))->toBe('fa')
        ->and($config->get('runtime.mode'))->toBe('production')
        ->and($config->get('runtime.debug'))->toBeFalse()
        ->and(Mode::name('com_test_app_env'))->toBe('production')
        ->and(app_env('THEME', null, 'com_test_app_env'))->toBe('spark');
});

it('lets theme .env override app .env for the same keys', function () {
    writeAppEnvTestApp(
        'com_test_app_env',
        ['theme' => 'default'],
        "THEME=default\nMODE=development\nDEBUG=true\n",
        "MODE=production\nDEBUG=false\n",
    );

    $config = AppEngine::config('com_test_app_env');

    expect($config->get('runtime.mode'))->toBe('production')
        ->and($config->get('runtime.debug'))->toBeFalse()
        ->and(Mode::name('com_test_app_env'))->toBe('production');
});

it('follows THEME in app .env when loading theme env file', function () {
    writeAppEnvTestApp(
        'com_test_app_env_alt',
        ['theme' => 'default'],
        "THEME=alt\n",
        null,
        "LANG=de\n",
    );

    expect(AppEngine::config('com_test_app_env_alt')->get('theme'))->toBe('alt')
        ->and(AppEngine::config('com_test_app_env_alt')->get('lang'))->toBe('de');
});

/**
 * @param array<string, mixed> $appConfig
 */
function writeAppEnvTestApp(
    string $package,
    array $appConfig,
    ?string $appEnv = null,
    ?string $themeEnv = null,
    ?string $altThemeEnv = null,
    array $extraFiles = [],
): void {
    $files = [
        'app.php' => '<?php return ' . var_export(array_merge(['package' => $package, 'enable' => true], $appConfig), true) . ';',
    ];

    if ($appEnv !== null) {
        $files['.env'] = $appEnv;
    }

    if ($themeEnv !== null) {
        $files['theme/default/.env'] = $themeEnv;
    }

    if ($altThemeEnv !== null) {
        $files['theme/alt/.env'] = $altThemeEnv;
    }

    foreach ($extraFiles as $relative => $content) {
        $files[$relative] = $content;
    }

    AppTestKit::fakeApp($package, $files);
}

it('merges layered app env files later-over-earlier', function () {
    putenv('APP_ENV=development');
    $_ENV['APP_ENV'] = 'development';
    $_SERVER['APP_ENV'] = 'development';

    writeAppEnvTestApp(
        'com_test_app_env_layers',
        ['theme' => 'default', 'lang' => 'en'],
        "MODE=development\nLANG=en\n",
        extraFiles: [
            '.env.local' => "LANG=fa\n",
            '.env.development' => "MODE=production\n",
        ],
    );

    $config = AppEngine::config('com_test_app_env_layers');

    expect($config->get('lang'))->toBe('fa')
        ->and($config->get('runtime.mode'))->toBe('production');
});

function deleteAppEnvTestApp(string $package): void
{
    if (is_dir(AppTestKit::path($package))) {
        AppTestKit::deleteFakeApp($package);
    }
}
