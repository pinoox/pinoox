<?php

use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Package\AppProvisioner;
use Pinoox\Component\Package\AppResource;
use Pinoox\Component\Package\AppResourceReference;
use Pinoox\Component\Package\Pinx\PinxBuilder;
use Pinoox\Component\Package\Pinx\PinxInstaller;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Component\Package\Pinx\PinxReader;
use Pinoox\Portal\App\AppEngine;

beforeEach(function () {
    appDepDeleteTestApp('com_test_dep_host');
    appDepDeleteTestApp('com_test_dep_client');
    appDepDeleteTestApp('com_test_dep_missing');
    appDepCleanupArtifacts();
    AppEngine::__rebuild();
});

afterEach(function () {
    appDepDeleteTestApp('com_test_dep_host');
    appDepDeleteTestApp('com_test_dep_client');
    appDepDeleteTestApp('com_test_dep_missing');
    appDepCleanupArtifacts();
});

it('embeds depends in pinx manifest from app.php', function () {
    appDepWriteTestApp('com_test_dep_host', ['version-code' => 3]);
    appDepWriteTestApp('com_test_dep_client', [
        'depends' => [
            'com_test_dep_host' => '>=2',
            'com_test_dep_missing' => ['optional' => true],
        ],
    ]);

    AppEngine::__rebuild();

    $result = appDepBuild('com_test_dep_client');
    $manifest = (new PinxReader())->open($result['path'])->manifest();

    expect($manifest->dependsRaw())->toMatchArray([
        'com_test_dep_host' => '>=2',
        'com_test_dep_missing' => ['optional' => true],
    ])
        ->and($manifest->depends())->toHaveCount(2);
});

it('rejects pinx install when a required dependency app is missing', function () {
    appDepWriteTestApp('com_test_dep_client', [
        'depends' => ['com_test_dep_host'],
    ]);
    AppEngine::__rebuild();

    $build = appDepBuild('com_test_dep_client');

    $result = (new PinxInstaller(AppEngine::___(), appDepWizardTmp()))
        ->install($build['path'], ['skip_migrate' => true, 'skip_patch' => true, 'skip_cache' => true]);

    expect($result->success)->toBeFalse()
        ->and($result->message)->toContain('com_test_dep_host');
});

it('rejects pinx install when dependency version code is too low', function () {
    appDepWriteTestApp('com_test_dep_host', ['version-code' => 1]);
    appDepWriteTestApp('com_test_dep_client', [
        'depends' => ['com_test_dep_host' => '>=3'],
    ]);
    AppEngine::__rebuild();

    $build = appDepBuild('com_test_dep_client');

    $result = (new PinxInstaller(AppEngine::___(), appDepWizardTmp()))
        ->install($build['path'], ['skip_migrate' => true, 'skip_patch' => true, 'skip_cache' => true]);

    expect($result->success)->toBeFalse()
        ->and($result->message)->toContain('version code 3');
});

it('installs pinx package when required dependencies are satisfied', function () {
    appDepWriteTestApp('com_test_dep_host', ['version-code' => 5]);
    appDepWriteTestApp('com_test_dep_client', [
        'depends' => ['com_test_dep_host' => '>=2'],
    ]);
    AppEngine::__rebuild();

    $build = appDepBuild('com_test_dep_client');

    appDepDeleteTestApp('com_test_dep_client');
    AppEngine::__rebuild();

    $result = (new PinxInstaller(AppEngine::___(), appDepWizardTmp()))
        ->install($build['path'], ['skip_migrate' => true, 'skip_patch' => true, 'skip_cache' => true]);

    expect($result->success)->toBeTrue()
        ->and(AppEngine::___()->exists('com_test_dep_client'))->toBeTrue();
});

it('normalizes dependency rules from list and map forms', function () {
    $rules = AppDependency::normalize([
        'com_a' => '>=2',
        'com_b',
        'com_c' => ['optional' => true, 'min_code' => 1],
    ]);

    expect($rules)->toHaveCount(3)
        ->and($rules[0]['package'])->toBe('com_a')
        ->and($rules[0]['min_code'])->toBe(2)
        ->and($rules[1]['package'])->toBe('com_b')
        ->and($rules[2]['optional'])->toBeTrue();
});

it('resolves cross-app resource references', function () {
    appDepWriteTestApp('com_test_dep_host', [
        'name' => 'Host App',
        'version-code' => 7,
    ]);
    AppEngine::__rebuild();

    $host = use_app('com_test_dep_host');

    expect($host->exists())->toBeTrue()
        ->and($host->versionCode())->toBe(7)
        ->and($host->path('marker.txt'))->toEndWith('marker.txt')
        ->and(AppResourceReference::parse('@com_test_dep_host:path.marker.txt')->type)
        ->toBe(AppResourceReference::TYPE_PATH);
});

it('uses when() helper only if dependency exists', function () {
    appDepWriteTestApp('com_test_dep_host');
    AppEngine::__rebuild();

    $called = use_app('com_test_dep_host')->when(static fn () => true, false);
    $skipped = use_app('com_test_dep_missing')->when(static fn () => true, false);

    expect($called)->toBeTrue()
        ->and($skipped)->toBeFalse();
});

function appDepBuild(string $package): array
{
    $output = appDepTempFile($package . '_' . uniqid('', true) . '.pinx');

    return (new PinxBuilder(AppEngine::___()))->build($package, $output);
}

function appDepWriteTestApp(string $package, array $extra = []): void
{
    $dir = appDepAppDir($package);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $config = array_merge([
        'package' => $package,
        'enable' => true,
        'name' => 'Dep Test ' . $package,
        'version-name' => '1.0.0',
        'version-code' => 1,
        'theme' => 'default',
        'router' => ['routes' => []],
    ], $extra);

    $export = var_export($config, true);
    file_put_contents($dir . '/app.php', "<?php\n\nreturn {$export};\n");
    file_put_contents($dir . '/marker.txt', $package);

    $themeDir = $dir . '/theme/default';
    if (!is_dir($themeDir)) {
        mkdir($themeDir, 0777, true);
    }
}

function appDepDeleteTestApp(string $package): void
{
    appDepDeleteDirectory(appDepAppDir($package));
    appDepDeleteDirectory(testProjectRoot() . '/pinker/apps/' . $package);
}

function appDepDeleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
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

function appDepAppDir(string $package): string
{
    return testProjectRoot() . '/apps/' . $package;
}

function appDepCleanupArtifacts(): void
{
    $dir = testFixtures('app_dep');
    if (!is_dir($dir)) {
        return;
    }

    foreach (glob($dir . '/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

function appDepTempFile(string $name): string
{
    $dir = testFixtures('app_dep');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir . '/' . $name;
}

function appDepWizardTmp(): string
{
    return \Pinoox\Support\SystemConfig::path('wizard_tmp');
}

it('sorts install candidates by required dependencies', function () {
    appDepWriteTestApp('com_test_dep_host', ['enable' => true, 'version-code' => 1]);
    appDepWriteTestApp('com_test_dep_client', [
        'enable' => true,
        'depends' => ['com_test_dep_host'],
    ]);
    AppEngine::__rebuild();

    $ordered = AppDependency::sortForInstall(
        ['com_test_dep_client', 'com_test_dep_host'],
        AppEngine::___(),
    );

    expect($ordered)->toBe(['com_test_dep_host', 'com_test_dep_client']);
});

it('provisions enabled apps in dependency order during project setup', function () {
    appDepWriteTestApp('com_test_dep_host', ['enable' => true, 'lang' => 'en']);
    appDepWriteTestApp('com_test_dep_client', [
        'enable' => true,
        'depends' => ['com_test_dep_host'],
        'lang' => 'fa',
    ]);
    AppEngine::__rebuild();

    $provisioned = (new AppProvisioner(AppEngine::___()))->provisionInstalledApps([
        'exclude' => ['com_pinoox_installer'],
        'lang' => 'en',
        'only_enabled' => true,
        'skip_migrate' => true,
        'skip_patch' => true,
        'skip_cache' => true,
    ]);

    expect($provisioned)->toContain('com_test_dep_host', 'com_test_dep_client')
        ->and(array_search('com_test_dep_host', $provisioned, true))
        ->toBeLessThan(array_search('com_test_dep_client', $provisioned, true));
});

it('rejects bulk provision when a required dependency app is missing', function () {
    appDepWriteTestApp('com_test_dep_client', [
        'enable' => true,
        'depends' => ['com_test_dep_missing'],
    ]);
    AppEngine::__rebuild();

    expect(fn () => (new AppProvisioner(AppEngine::___()))->provisionInstalledApps([
        'only_enabled' => true,
        'skip_migrate' => true,
        'skip_patch' => true,
        'skip_cache' => true,
    ]))->toThrow(\Pinoox\Component\Kernel\Exception::class, 'com_test_dep_missing');
});

