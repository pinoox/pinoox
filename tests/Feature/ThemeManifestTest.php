<?php
use Pinoox\Component\Package\Pinx\PinxBuilder;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Component\Template\Theme\ThemeManifest;
use Pinoox\Component\Template\Theme\ThemeStack;
use Pinoox\Component\Test\AppTestKit;
use Pinoox\Portal\App\AppEngine;
beforeEach(function () {
    AppTestKit::boot();
    themeManifestDeleteApp('com_test_theme_manifest');
    AppEngine::__rebuild();
});
afterEach(function () {
    themeManifestDeleteApp('com_test_theme_manifest');
    AppEngine::__rebuild();
});
it('loads localized title and extends from theme.php', function () {
    themeManifestWriteApp([
        'toranj/theme.php' => themeManifestPhp([
            'name' => 'toranj',
            'package' => 'com_test_theme_manifest',
            'extends' => ['blue'],
            'developer' => 'pinoox',
            'title' => ['en' => 'Toranj', 'fa' => 'ترنج'],
            'description' => ['en' => 'Minimal blog template'],
            'version-name' => '1.0',
            'version-code' => 2,
            'api' => true,
        ]),
        'blue/theme.php' => themeManifestPhp([
            'name' => 'blue',
            'package' => 'com_test_theme_manifest',
            'title' => ['en' => 'Blue'],
        ]),
    ]);
    $manifest = ThemeManifest::load('com_test_theme_manifest', 'toranj');
    expect($manifest)->not->toBeNull()
        ->and($manifest->name())->toBe('toranj')
        ->and($manifest->hostPackage())->toBe('com_test_theme_manifest')
        ->and($manifest->extends())->toBe(['blue'])
        ->and($manifest->title('fa'))->toBe('ترنج')
        ->and($manifest->hasApiShell())->toBeTrue()
        ->and($manifest->versionCode())->toBe(2);
});
it('discovers installed themes with theme.php', function () {
    themeManifestWriteApp([
        'blue/theme.php' => themeManifestPhp([
            'name' => 'blue',
            'package' => 'com_test_theme_manifest',
            'title' => ['en' => 'Blue'],
        ]),
        'toranj/theme.php' => themeManifestPhp([
            'name' => 'toranj',
            'package' => 'com_test_theme_manifest',
            'title' => ['en' => 'Toranj'],
        ]),
    ]);
    $themes = ThemeManifest::discover('com_test_theme_manifest');
    expect(array_keys($themes))->toBe(['blue', 'toranj']);
});
it('embeds theme meta in pinx manifest for theme packages', function () {
    themeManifestWriteApp([
        'spark/theme.txt' => 'spark',
        'spark/theme.php' => themeManifestPhp([
            'name' => 'spark',
            'package' => 'com_test_theme_manifest',
            'developer' => 'pinoox team',
            'title' => ['en' => 'Spark'],
            'version-name' => '2.0',
            'version-code' => 3,
        ]),
    ], [
        'pinx' => [
            'type' => 'theme',
            'target_app' => 'com_test_theme_manifest',
            'theme_name' => 'spark',
        ],
        'theme' => 'spark',
    ]);
    AppEngine::__rebuild();
    $result = (new PinxBuilder(AppEngine::___()))->build(
        'com_test_theme_manifest',
        themeManifestTempFile('spark.pinx'),
    );
    $manifest = (new \Pinoox\Component\Package\Pinx\PinxReader())
        ->open($result['path'])
        ->manifest();
    expect($manifest->type())->toBe(PinxManifest::TYPE_THEME)
        ->and($manifest->package())->toBe('spark')
        ->and($manifest->targetApp())->toBe('com_test_theme_manifest')
        ->and($manifest->developer())->toBe('pinoox team')
        ->and($manifest->versionCode())->toBe(3);
});
it('builds inheritance stack from nested theme.php extends', function () {
    themeManifestWriteApp([
        'child/theme.php' => themeManifestPhp([
            'name' => 'child',
            'package' => 'com_test_theme_manifest',
            'extends' => ['parent'],
        ]),
        'parent/theme.php' => themeManifestPhp([
            'name' => 'parent',
            'package' => 'com_test_theme_manifest',
        ]),
        'child/page.twig' => 'child-page',
        'parent/layout.twig' => '<html>{% block body %}{% endblock %}</html>',
    ], ['theme' => 'child']);
    AppEngine::__rebuild();
    expect(ThemeStack::resolve('com_test_theme_manifest')['stack'])->toBe(['child', 'parent']);
});
function themeManifestPhp(array $data): string
{
    return "<?php\n\nreturn " . var_export($data, true) . ";\n";
}
function themeManifestWriteApp(array $themeFiles, array $appExtra = []): void
{
    $dir = themeManifestAppDir();
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $app = array_merge([
        'package' => 'com_test_theme_manifest',
        'enable' => true,
        'name' => 'Theme Manifest Test',
        'theme' => 'default',
        'router' => ['routes' => []],
    ], $appExtra);
    file_put_contents($dir . '/app.php', "<?php\n\nreturn " . var_export($app, true) . ";\n");
    foreach ($themeFiles as $relative => $content) {
        $target = $dir . '/theme/' . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $folder = dirname($target);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        file_put_contents($target, $content);
    }
}
function themeManifestDeleteApp(string $package): void
{
    themeManifestDeleteDirectory(dirname(__DIR__, 2) . '/apps/' . $package);
    themeManifestDeleteDirectory(dirname(__DIR__, 2) . '/pinker/apps/' . $package);
    themeManifestDeleteDirectory(dirname(__DIR__) . '/Fixtures/theme_manifest');
}
function themeManifestAppDir(): string
{
    return dirname(__DIR__, 2) . '/apps/com_test_theme_manifest';
}
function themeManifestTempFile(string $name): string
{
    $dir = dirname(__DIR__) . '/Fixtures/theme_manifest';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir . '/' . $name;
}
function themeManifestDeleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
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

