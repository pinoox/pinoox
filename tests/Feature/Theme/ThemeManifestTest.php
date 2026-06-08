<?php
use Pinoox\Component\Package\AppEnv\AppEnvBridge;
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
it('loads theme manifest through pinker with theme-source defaults', function () {
    themeManifestWriteApp([
        'toranj/theme.php' => themeManifestPhp([
            'name' => 'toranj',
            'package' => 'com_test_theme_manifest',
            'title' => ['en' => 'Toranj'],
        ]),
    ]);

    $overrideDir = testProjectRoot() . '/pinker/state/apps/com_test_theme_manifest/theme/toranj';
    if (!is_dir($overrideDir)) {
        mkdir($overrideDir, 0777, true);
    }
    file_put_contents($overrideDir . '/theme.php', <<<'PHP'
<?php
return [
    '__pinker_override__' => true,
    'schema' => 1,
    'data' => [
        'developer' => 'pinker team',
    ],
    'remove' => [],
];
PHP);

    $manifest = ThemeManifest::load('com_test_theme_manifest', 'toranj');

    expect($manifest)->not->toBeNull()
        ->and($manifest->developer())->toBe('pinker team')
        ->and($manifest->copyright())->toBe('MIT')
        ->and($manifest->cover())->toBe('cover.png');
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

    AppEnvBridge::reset();
    AppEngine::__rebuild();
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
    themeManifestDeleteDirectory(testProjectRoot() . '/apps/' . $package);
    themeManifestDeleteDirectory(testProjectRoot() . '/pinker/apps/' . $package);
    themeManifestDeleteDirectory(testProjectRoot() . '/pinker/state/apps/' . $package);
    themeManifestDeleteDirectory(testFixtures('theme_manifest'));
    AppEnvBridge::reset();
}
function themeManifestAppDir(): string
{
    return testProjectRoot() . '/apps/com_test_theme_manifest';
}
function themeManifestTempFile(string $name): string
{
    $dir = testFixtures('theme_manifest');
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

