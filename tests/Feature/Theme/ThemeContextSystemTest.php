<?php

use Pinoox\Component\Template\Theme\ThemeContext;
use Pinoox\Component\Template\Theme\ThemeContextRegistry;
use Pinoox\Component\Template\Theme\ThemeStack;
use Pinoox\Component\Template\View;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Flow\ThemeContextFlow;
use Pinoox\Portal\App\AppEngine;

beforeEach(function () {
    AppTestKit::boot();
    ThemeContext::clearAll();
    deleteThemeContextTestApp('com_test_theme_ctx');
    AppEngine::__rebuild();
});

afterEach(function () {
    ThemeContext::clearAll();
    deleteThemeContextTestApp('com_test_theme_ctx');
    AppEngine::__rebuild();
});

it('resolves different theme folders per context', function () {
    writeThemeContextTestApp([
        'theme-context' => 'site',
        'theme-contexts' => [
            'site' => ['theme' => 'site'],
            'panel' => ['theme' => 'panel'],
            'kids' => ['theme' => 'kids', 'extends' => 'site'],
        ],
    ]);
    AppEngine::__rebuild();

    $site = ThemeStack::resolve('com_test_theme_ctx', 'site');
    $panel = ThemeStack::resolve('com_test_theme_ctx', 'panel');
    $kids = ThemeStack::resolve('com_test_theme_ctx', 'kids');

    expect(basename($site['paths'][0]))->toBe('site')
        ->and(basename($panel['paths'][0]))->toBe('panel')
        ->and($kids['stack'])->toBe(['kids', 'site']);
});

it('activates a theme context and switches view paths', function () {
    writeThemeContextTestApp([
        'theme-context' => 'site',
        'theme-contexts' => [
            'site' => ['theme' => 'site'],
            'panel' => ['theme' => 'panel'],
        ],
    ], [
        'site/page.twig' => 'SITE',
        'panel/page.twig' => 'PANEL',
    ]);
    AppEngine::__rebuild();

    ThemeContext::activate('site', 'com_test_theme_ctx');
    $siteView = new View(ThemeStack::resolve('com_test_theme_ctx', 'site')['paths'], '', []);
    expect($siteView->render('page.twig'))->toBe('SITE');

    ThemeContext::activate('panel', 'com_test_theme_ctx');
    $panelView = new View(ThemeStack::resolve('com_test_theme_ctx', 'panel')['paths'], '', []);
    expect($panelView->render('page.twig'))->toBe('PANEL');
});

it('builds theme flow aliases for route collections', function () {
    $aliases = theme_flow_aliases(['site', 'panel', 'kids']);

    expect($aliases['theme']['panel'])->toBeInstanceOf(ThemeContextFlow::class)
        ->and($aliases['theme']['site'])->toBeInstanceOf(ThemeContextFlow::class);
});

it('keeps backward compatibility when theme-contexts is empty', function () {
    writeThemeContextTestApp([
        'theme' => 'default',
        'theme-contexts' => [],
    ], [
        'default/page.twig' => 'DEFAULT',
    ]);
    AppEngine::__rebuild();

    expect(ThemeContextRegistry::hasContexts(include appThemeContextDir() . '/app.php'))->toBeFalse()
        ->and(ThemeStack::resolve('com_test_theme_ctx')['name'])->toBe('default');
});

it('restores previous context after within_theme()', function () {
    writeThemeContextTestApp([
        'theme-context' => 'site',
        'theme-contexts' => [
            'site' => ['theme' => 'site'],
            'panel' => ['theme' => 'panel'],
        ],
    ]);
    AppEngine::__rebuild();

    ThemeContext::activate('site', 'com_test_theme_ctx');

    within_theme('panel', function () {
        expect(ThemeContext::active('com_test_theme_ctx'))->toBe('panel');
    }, 'com_test_theme_ctx');

    expect(ThemeContext::active('com_test_theme_ctx'))->toBe('site');
});

function writeThemeContextTestApp(array $config, array $themeFiles = []): void
{
    $dir = appThemeContextDir();
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $app = array_merge([
        'package' => 'com_test_theme_ctx',
        'enable' => true,
        'name' => 'Theme Context Test',
        'version-code' => 1,
        'router' => ['routes' => []],
    ], $config);

    file_put_contents($dir . '/app.php', "<?php\n\nreturn " . var_export($app, true) . ";\n");

    foreach ($themeFiles as $relative => $content) {
        $path = $dir . '/theme/' . $relative;
        $folder = dirname($path);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        file_put_contents($path, $content);
    }

    foreach (['site', 'panel', 'kids', 'default'] as $theme) {
        $marker = $dir . '/theme/' . $theme;
        if (!is_dir($marker)) {
            mkdir($marker, 0777, true);
        }
    }
}

function deleteThemeContextTestApp(string $package): void
{
    themeContextDeleteDirectory(testProjectRoot() . '/apps/' . $package);
    themeContextDeleteDirectory(testProjectRoot() . '/pinker/apps/' . $package);
}

function appThemeContextDir(): string
{
    return testProjectRoot() . '/apps/com_test_theme_ctx';
}

function themeContextDeleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

