<?php

use Pinoox\Component\Template\Theme\ThemeReference;
use Pinoox\Component\Template\Theme\ThemeStack;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Portal\App\AppEngine;

beforeEach(function () {
    AppTestKit::boot();
    deleteThemeInheritanceTestApp('com_test_theme_child');
    AppEngine::__rebuild();
});

afterEach(function () {
    deleteThemeInheritanceTestApp('com_test_theme_child');
    AppEngine::__rebuild();
});

it('builds a child-first theme stack from app config', function () {
    scaffoldThemeInheritanceApps();

    $stack = ThemeStack::resolve('com_test_theme_child');

    expect($stack['name'])->toBe('brand')
        ->and($stack['stack'])->toBe(['brand', 'default'])
        ->and($stack['paths'])->toHaveCount(2)
        ->and(basename($stack['paths'][0]))->toBe('brand')
        ->and(basename($stack['paths'][1]))->toBe('default');
});

it('reads extends from theme.php manifest', function () {
    writeThemeInheritanceApp('com_test_theme_child', [
        'theme' => 'brand',
    ], [
        'default/layout.twig' => '<html>{% block body %}base{% endblock %}</html>',
        'brand/page.twig' => 'child',
        'brand/theme.php' => "<?php\n\nreturn ['name' => 'brand', 'package' => 'com_test_theme_child', 'extends' => ['default'], 'title' => ['en' => 'Brand']];\n",
    ]);
    AppEngine::__rebuild();

    $stack = ThemeStack::resolve('com_test_theme_child');

    expect($stack['stack'])->toBe(['brand', 'default']);
});

it('prefers theme.php extends over app.php theme-extends', function () {
    writeThemeInheritanceApp('com_test_theme_child', [
        'theme' => 'brand',
        'theme-extends' => 'ignored',
    ], [
        'default/layout.twig' => 'base',
        'ignored/layout.twig' => 'ignored',
        'brand/theme.php' => "<?php\n\nreturn ['name' => 'brand', 'package' => 'com_test_theme_child', 'extends' => ['default']];\n",
    ]);
    AppEngine::__rebuild();

    expect(ThemeStack::resolve('com_test_theme_child')['stack'])->toBe(['brand', 'default']);
});

it('parses cross-app theme references', function () {
    $ref = ThemeReference::parse('@com_test_theme_base/default', 'com_test_theme_child');

    expect($ref->package)->toBe('com_test_theme_base')
        ->and($ref->name)->toBe('default');
});

it('resolves templates and assets from parent themes', function () {
    scaffoldThemeInheritanceApps();

    $stack = ThemeStack::resolve('com_test_theme_child');
    $view = new \Pinoox\Component\Template\View($stack['paths'], '', []);
    $path = $view->path();

    expect($path->exists('layout.twig'))->toBeTrue()
        ->and($path->exists('page.twig'))->toBeTrue()
        ->and($path->file('layout.twig'))->toContain('theme/default/layout.twig')
        ->and($path->file('page.twig'))->toContain('theme/brand/page.twig')
        ->and($view->render('page.twig'))->toContain('child-page')
        ->and($view->render('page.twig'))->toContain('<html>');
});

it('detects absolute theme asset paths for public url conversion', function () {
    scaffoldThemeInheritanceApps();

    $logoPath = testProjectRoot() . '/apps/com_test_theme_child/theme/brand/logo.png';
    file_put_contents($logoPath, 'png');

    $stack = ThemeStack::resolve('com_test_theme_child');
    $view = new \Pinoox\Component\Template\View($stack['paths'], '', []);
    $filesystemPath = str_replace('\\', '/', $view->assets('logo.png', true));
    $basePath = rtrim(str_replace('\\', '/', testProjectRoot()), '/');

    expect($view->isFilesystemPath($filesystemPath))->toBeTrue()
        ->and(assets_is_filesystem_path($filesystemPath))->toBeTrue()
        ->and(ltrim(substr($filesystemPath, strlen($basePath)), '/'))
        ->toBe('apps/com_test_theme_child/theme/brand/logo.png');

    @unlink($logoPath);
});

it('builds public theme asset urls without leaking filesystem paths', function () {
    scaffoldThemeInheritanceApps();

    $logoPath = testProjectRoot() . '/apps/com_test_theme_child/theme/brand/logo.png';
    file_put_contents($logoPath, 'png');

    $stack = ThemeStack::resolve('com_test_theme_child');
    $view = new \Pinoox\Component\Template\View($stack['paths'], '', []);

    expect(\Pinoox\Component\Template\Theme\ThemeAssets::publicSegment(
        new \Pinoox\Component\Template\Theme\ThemeReference('com_test_theme_child', 'brand'),
        'logo.png',
        'theme',
    ))->toBe('theme/brand/logo.png');

    @unlink($logoPath);
});

it('resolves assets from another theme folder in the same app', function () {
    scaffoldThemeInheritanceApps();

    $parentLogo = testProjectRoot() . '/apps/com_test_theme_child/theme/default/parent-logo.png';
    file_put_contents($parentLogo, 'png');

    ['file' => $file, 'theme' => $theme] = \Pinoox\Component\Template\Theme\ThemeAssets::parseThemedLink('@default/parent-logo.png');

    expect($theme)->toBe('default')
        ->and($file)->toBe('parent-logo.png')
        ->and(\Pinoox\Component\Template\Theme\ThemeAssets::publicSegment(
            new ThemeReference('com_test_theme_child', 'default'),
            'parent-logo.png',
            'theme',
        ))->toBe('theme/default/parent-logo.png');

    $filesystem = \Pinoox\Component\Template\Theme\ThemeAssets::filesystem(
        'parent-logo.png',
        'default',
        'com_test_theme_child',
    );

    expect(str_replace('\\', '/', $filesystem))->toContain('theme/default/parent-logo.png');

    @unlink($parentLogo);
});

it('detects circular theme inheritance', function () {
    writeThemeInheritanceApp('com_test_theme_child', [
        'theme' => 'brand',
    ], [
        'default/theme.php' => "<?php\n\nreturn ['extends' => 'brand'];\n",
        'brand/theme.php' => "<?php\n\nreturn ['extends' => 'default'];\n",
        'brand/page.twig' => 'ok',
    ]);
    AppEngine::__rebuild();

    expect(fn () => ThemeStack::resolve('com_test_theme_child'))
        ->toThrow(\RuntimeException::class, 'Circular theme inheritance');
});

function scaffoldThemeInheritanceApps(bool $withThemePhp = false): void
{
    writeThemeInheritanceApp('com_test_theme_child', [
        'theme' => 'brand',
        'theme-extends' => $withThemePhp ? null : 'default',
    ], [
        'default/layout.twig' => "<html>{% block body %}base-layout{% endblock %}</html>",
        'default/functions.php' => "<?php\nfunction theme_base_fn() { return 'base'; }\n",
        'brand/page.twig' => "{% extends 'layout.twig' %}\n{% block body %}child-page{% endblock %}",
        ...($withThemePhp ? ['brand/theme.php' => "<?php\n\nreturn ['name' => 'brand', 'package' => 'com_test_theme_child', 'extends' => 'default'];\n"] : []),
    ]);

    AppEngine::__rebuild();
}

function writeThemeInheritanceApp(string $package, array $config, array $themeFiles): void
{
    $dir = testProjectRoot() . '/apps/' . $package;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($dir . '/app.php', "<?php\n\nreturn " . var_export([
        'package' => $package,
        'enable' => true,
        'name' => $package,
        ...$config,
    ], true) . ";\n");

    foreach ($themeFiles as $relative => $content) {
        $target = $dir . '/theme/' . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $folder = dirname($target);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        file_put_contents($target, $content);
    }
}

function deleteThemeInheritanceTestApp(string $package): void
{
    $root = testProjectRoot();
    deleteThemeInheritanceDirectory($root . '/apps/' . $package);
    deleteThemeInheritanceDirectory($root . '/pinker/apps/' . $package);
}

function deleteThemeInheritanceDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

