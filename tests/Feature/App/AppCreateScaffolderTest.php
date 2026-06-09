<?php

use Pinoox\Component\Package\Scaffold\AppCreateInput;
use Pinoox\Component\Package\Scaffold\AppCreateScaffolder;
use Pinoox\Portal\FileSystem;
use Pinoox\Support\SystemConfig;

afterEach(function () {
    foreach (['com_test_create_none', 'com_test_create_vue', 'com_test_create_vite', 'com_test_read_package'] as $package) {
        $dir = SystemConfig::path('apps') . '/' . $package;
        if (is_dir($dir)) {
            FileSystem::remove($dir);
        }
    }
});

it('scaffolds a twig-only app without vite files', function () {
    $input = new AppCreateInput(
        package: 'com_test_create_none',
        displayName: 'Test None',
        developer: 'Tester',
        description: 'Twig only app',
        stack: AppCreateInput::STACK_NONE,
    );

    $scaffolder = new AppCreateScaffolder($input);
    expect($scaffolder->exists())->toBeFalse();

    $result = $scaffolder->scaffold();

    expect($result->stack)->toBe('none')
        ->and(is_file($scaffolder->appDir() . '/app.php'))->toBeTrue()
        ->and(is_file($scaffolder->appDir() . '/theme/default/hello.twig'))->toBeTrue()
        ->and(is_file($scaffolder->appDir() . '/theme/default/package.json'))->toBeFalse()
        ->and(is_file($scaffolder->appDir() . '/tests/package.php'))->toBeFalse()
        ->and(is_file($scaffolder->appDir() . '/tests/bootstrap.php'))->toBeTrue()
        ->and(is_file($scaffolder->appDir() . '/tests/README.md'))->toBeTrue();

    $appConfig = include $scaffolder->appDir() . '/app.php';
    expect($appConfig)->not->toHaveKey('frontend')
        ->and(\Pinoox\Component\Test\AppTestKit::packageFromAppDir($scaffolder->appDir()))->toBe('com_test_create_none');
});

it('scaffolds a vue hybrid app with vite frontend files', function () {
    $input = new AppCreateInput(
        package: 'com_test_create_vue',
        displayName: 'Test Vue',
        developer: 'Tester',
        description: 'Vue hybrid app',
        stack: AppCreateInput::STACK_VUE,
        profile: AppCreateInput::PROFILE_HYBRID,
    );

    $result = (new AppCreateScaffolder($input))->scaffold();
    $appDir = SystemConfig::path('apps') . '/com_test_create_vue';

    expect($result->stack)->toBe('vue')
        ->and($result->profile)->toBe('hybrid')
        ->and(is_file($appDir . '/theme/default/vite.config.js'))->toBeTrue()
        ->and(is_file($appDir . '/theme/default/package.json'))->toBeTrue()
        ->and(is_file($appDir . '/theme/default/frontend.config.php'))->toBeTrue();

    $appConfig = include $appDir . '/app.php';
    expect($appConfig['frontend']['profile'])->toBe('hybrid')
        ->and($appConfig['frontend']['stack'])->toBe('vue');

    $frontendConfig = include $appDir . '/theme/default/frontend.config.php';
    expect($frontendConfig['profile'])->toBe('hybrid')
        ->and($frontendConfig['stack'])->toBe('vue');

    $controller = (string) file_get_contents($appDir . '/Controller/MainController.php');
    expect($controller)->toContain('shareSeo');

    $bootTest = (string) file_get_contents($appDir . '/tests/Feature/AppBootTest.php');
    expect($bootTest)->toContain('inMyApp')
        ->and($bootTest)->toContain('appUnderTest');

    $controllerFile = $appDir . '/Controller/MainController.php';
    expect(is_file($controllerFile))->toBeTrue();
    $lint = 0;
    exec('php -l ' . escapeshellarg($controllerFile), $output, $lint);
    expect($lint)->toBe(0);
});

it('scaffolds a vite-only app without vue or react', function () {
    $input = new AppCreateInput(
        package: 'com_test_create_vite',
        displayName: 'Test Vite',
        developer: 'Tester',
        description: 'Vite only app',
        stack: AppCreateInput::STACK_VITE,
        profile: AppCreateInput::PROFILE_HYBRID,
    );

    $result = (new AppCreateScaffolder($input))->scaffold();
    $appDir = SystemConfig::path('apps') . '/com_test_create_vite';

    expect($result->stack)->toBe('vite')
        ->and(is_file($appDir . '/theme/default/vite.config.js'))->toBeTrue()
        ->and(is_file($appDir . '/theme/default/package.json'))->toBeTrue()
        ->and(is_file($appDir . '/theme/default/src/main.js'))->toBeTrue();

    $package = json_decode((string) file_get_contents($appDir . '/theme/default/package.json'), true);
    expect($package['dependencies'] ?? [])->not->toHaveKey('vue')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('react');

    $appConfig = include $appDir . '/app.php';
    expect($appConfig['frontend']['stack'])->toBe('vite');
});

it('reads package from app.php for app tests', function () {
    $input = new AppCreateInput(
        package: 'com_test_read_package',
        displayName: 'Test',
        developer: 'Tester',
        description: 'Test',
        stack: AppCreateInput::STACK_NONE,
    );

    $appDir = (new AppCreateScaffolder($input))->scaffold()->appDir;
    $bootstrap = (string) file_get_contents($appDir . '/tests/bootstrap.php');

    expect($bootstrap)->toContain('packageFromAppDir')
        ->and($bootstrap)->not->toContain('package.php');
});

it('builds simple app input with twig-only defaults', function () {
    $input = AppCreateInput::simple('com_my_blog');

    expect($input->package)->toBe('com_my_blog')
        ->and($input->displayName)->toBe('Blog')
        ->and($input->stack)->toBe('none')
        ->and($input->registerRoute)->toBeFalse();
});

it('normalizes package names with com_ prefix', function () {
    expect(AppCreateScaffolder::normalizePackageName('my_shop'))->toBe('com_my_shop')
        ->and(AppCreateScaffolder::normalizePackageName('com_acme_blog'))->toBe('com_acme_blog');
});
