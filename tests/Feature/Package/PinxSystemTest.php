<?php

use Pinoox\Component\Package\AppComposerVendor;
use Pinoox\Component\Package\Pinx\PinxBuilder;
use Pinoox\Component\Package\Pinx\PinxFileSelector;
use Pinoox\Component\Package\Pinx\PinxIdentity;
use Pinoox\Component\Package\Pinx\PinxInstaller;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Component\Package\Pinx\PinxReader;
use Pinoox\Component\Package\Pinx\PinxSignKey;
use Pinoox\Component\Package\Pinx\PinxVersion;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;
use Pinoox\Terminal\Pinx\PinxInfoCommand;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    pinxSystemDeleteTestApp('com_test_pinx');
    pinxSystemDeleteTestApp('com_test_pinx_clone');
    pinxSystemCleanupArtifacts();
    AppEngine::__rebuild();
});

afterEach(function () {
    pinxSystemDeleteTestApp('com_test_pinx');
    pinxSystemDeleteTestApp('com_test_pinx_clone');
    pinxSystemCleanupArtifacts();
});

function pinxSystemBuild(string $package, array $options = []): array
{
    $output = pinxSystemTempFile($package . '_' . uniqid('', true) . '.pinx');

    return (new PinxBuilder(AppEngine::___()))->build($package, $output, $options);
}

function pinxSystemGenerateKey(string $package): string
{
    if (!function_exists('sodium_crypto_sign_keypair')) {
        test()->markTestSkipped('PHP sodium extension is required for pinx signing tests.');
    }

    $dir = pinxSystemAppDir($package) . '/pinx';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $path = $dir . '/' . PinxSignKey::KEY_FILE;
    $key = PinxSignKey::generate($package);
    PinxSignKey::save($key, $path);

    return $path;
}

it('builds an app pinx package with manifest metadata', function () {
    pinxSystemWriteTestApp('com_test_pinx', [
        'pinx' => ['type' => 'app', 'minpin' => 0],
        'build' => ['gitignore' => false, 'exclude' => ['export']],
    ]);

    AppEngine::__rebuild();

    $result = pinxSystemBuild('com_test_pinx');
    $manifest = (new PinxReader())->open($result['path'])->manifest();

    expect($result['files'])->toBeGreaterThan(0)
        ->and($manifest->type())->toBe(PinxManifest::TYPE_APP)
        ->and($manifest->package())->toBe('com_test_pinx')
        ->and($manifest->versionCode())->toBe(2);
});

it('builds a theme pinx package targeting its host app', function () {
    pinxSystemWriteTestApp('com_test_pinx', [
        'theme' => 'spark',
        'pinx' => [
            'type' => 'theme',
            'target_app' => 'com_test_pinx',
            'theme_name' => 'spark',
        ],
    ], withTheme: 'spark', withThemeMeta: true);

    AppEngine::__rebuild();

    $result = pinxSystemBuild('com_test_pinx');
    $manifest = (new PinxReader())->open($result['path'])->manifest();

    expect($manifest->type())->toBe(PinxManifest::TYPE_THEME)
        ->and($manifest->targetApp())->toBe('com_test_pinx')
        ->and($manifest->themeName())->toBe('spark');
});

it('rejects install when minpin is higher than current pinoox version', function () {
    pinxSystemWriteTestApp('com_test_pinx');
    AppEngine::__rebuild();

    $build = pinxSystemBuild('com_test_pinx');
    $manifestData = json_decode(file_get_contents('zip://' . $build['path'] . '#manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $manifestData['minpin'] = 999999;
    file_put_contents(pinxSystemTempFile('manifest.json'), json_encode($manifestData));

    $archive = new ZipArchive();
    $archive->open($build['path']);
    $archive->addFromString('manifest.json', json_encode($manifestData));
    $archive->close();

    $result = (new PinxInstaller(AppEngine::___(), SystemConfig::path('wizard_tmp')))
        ->install($build['path'], ['skip_migrate' => true, 'skip_patch' => true, 'skip_cache' => true]);

    expect($result->success)->toBeFalse()
        ->and($result->message)->toContain('requires Pinoox version code');
});

it('installs an app pinx package as update with skipped db steps', function () {
    pinxSystemWriteTestApp('com_test_pinx', ['version-code' => 2, 'name' => 'Pinx Test']);
    AppEngine::__rebuild();

    $build = pinxSystemBuild('com_test_pinx');

    pinxSystemWriteTestApp('com_test_pinx', ['version-code' => 1, 'name' => 'before-update']);
    AppEngine::__rebuild();

    $result = (new PinxInstaller(AppEngine::___(), SystemConfig::path('wizard_tmp')))
        ->install($build['path'], [
            'force' => true,
            'skip_migrate' => true,
            'skip_patch' => true,
            'skip_cache' => true,
        ]);

    $app = include pinxSystemAppFile('com_test_pinx');

    expect($result->success)->toBeTrue()
        ->and($result->mode)->toBe('update')
        ->and($app['version-code'])->toBe(2)
        ->and($app['name'])->toBe('Pinx Test');
});

it('shows package info through pinx:info command', function () {
    pinxSystemWriteTestApp('com_test_pinx');
    AppEngine::__rebuild();

    $build = pinxSystemBuild('com_test_pinx');

    $tester = new CommandTester(new PinxInfoCommand());
    $status = $tester->execute(['package' => $build['path']]);

    expect($status)->toBe(0)
        ->and($tester->getDisplay())->toContain('com_test_pinx')
        ->and($tester->getDisplay())->toContain('app');
});

it('evaluates minpin against pinoox version config', function () {
    $version = PinxVersion::pinoox();

    expect($version['code'])->not->toBeNull()
        ->and(PinxVersion::satisfiesMinpin(0))->toBeTrue()
        ->and(PinxVersion::satisfiesMinpin((int) $version['code']))->toBeTrue()
        ->and(PinxVersion::satisfiesMinpin(((int) $version['code']) + 1000))->toBeFalse();
});

it('builds composer install command without dev dependencies', function () {
    $command = AppComposerVendor::buildInstallCommand(pinxSystemAppDir('com_test_pinx'), testProjectRoot());

    expect($command)->toContain('install')
        ->and($command)->toContain('--no-dev')
        ->and($command)->toContain('--optimize-autoloader');
});

it('includes gitignored vendor directory in pinx payload when always included', function () {
    pinxSystemWriteTestApp('com_test_pinx', [
        'build' => ['gitignore' => true, 'composer' => false, 'exclude' => ['export']],
    ]);

    $dir = pinxSystemAppDir('com_test_pinx');
    file_put_contents($dir . '/.gitignore', "/vendor\n");
    mkdir($dir . '/vendor/nested', 0777, true);
    file_put_contents($dir . '/vendor/nested/lib.txt', 'vendor-lib');

    $files = (new PinxFileSelector())->payloadFiles($dir, [
        'gitignore' => true,
        'exclude' => ['export'],
        'always_include' => ['vendor'],
    ]);

    expect($files)->toHaveKey('marker.txt')
        ->and($files)->toHaveKey('vendor/nested/lib.txt');
});

it('builds a signed pinx package when a signing key exists', function () {
    pinxSystemWriteTestApp('com_test_pinx');
    pinxSystemGenerateKey('com_test_pinx');
    AppEngine::__rebuild();

    $result = pinxSystemBuild('com_test_pinx');
    $reader = new PinxReader();
    $reader->open($result['path']);

    expect($result['signed'])->toBeTrue()
        ->and($reader->signature())->not->toBeNull()
        ->and($reader->signature()['algorithm'] ?? '')->toBe(PinxSignKey::ALGORITHM);

    $reader->close();
});

it('installs a signed package and stores publisher identity', function () {
    pinxSystemWriteTestApp('com_test_pinx');
    pinxSystemGenerateKey('com_test_pinx');
    AppEngine::__rebuild();

    $build = pinxSystemBuild('com_test_pinx');

    pinxSystemDeleteTestApp('com_test_pinx');
    AppEngine::__rebuild();

    $result = (new PinxInstaller(AppEngine::___(), SystemConfig::path('wizard_tmp')))
        ->install($build['path'], [
            'skip_migrate' => true,
            'skip_patch' => true,
            'skip_cache' => true,
        ]);

    $identity = PinxIdentity::read(pinxSystemAppDir('com_test_pinx'));

    expect($result->success)->toBeTrue()
        ->and($identity)->not->toBeNull()
        ->and($identity['package'] ?? '')->toBe('com_test_pinx')
        ->and($identity['fingerprint'] ?? '')->not->toBe('');
});

it('rejects a tampered signed package on install', function () {
    pinxSystemWriteTestApp('com_test_pinx');
    pinxSystemGenerateKey('com_test_pinx');
    AppEngine::__rebuild();

    $build = pinxSystemBuild('com_test_pinx');

    $archive = new ZipArchive();
    $archive->open($build['path']);
    $payloadEntry = null;
    for ($i = 0; $i < $archive->numFiles; $i++) {
        $name = $archive->getNameIndex($i);
        if (is_string($name) && str_starts_with($name, 'payload/') && !str_ends_with($name, '/')) {
            $payloadEntry = $name;
            break;
        }
    }
    expect($payloadEntry)->not->toBeNull();
    $archive->addFromString($payloadEntry, 'tampered-content');
    $archive->close();

    $result = (new PinxInstaller(AppEngine::___(), SystemConfig::path('wizard_tmp')))
        ->install($build['path'], [
            'skip_migrate' => true,
            'skip_patch' => true,
            'skip_cache' => true,
        ]);

    expect($result->success)->toBeFalse()
        ->and($result->message)->toContain('modified after signing');
});

it('rejects update signed with a different publisher key', function () {
    pinxSystemWriteTestApp('com_test_pinx', ['version-code' => 1]);
    pinxSystemGenerateKey('com_test_pinx');
    AppEngine::__rebuild();

    $initial = pinxSystemBuild('com_test_pinx');

    pinxSystemDeleteTestApp('com_test_pinx');
    AppEngine::__rebuild();

    (new PinxInstaller(AppEngine::___(), SystemConfig::path('wizard_tmp')))
        ->install($initial['path'], [
            'skip_migrate' => true,
            'skip_patch' => true,
            'skip_cache' => true,
        ]);

    pinxSystemWriteTestApp('com_test_pinx', ['version-code' => 2, 'name' => 'Updated']);
    $otherKeyPath = pinxSystemTempFile('other.key.json');
    $otherKey = PinxSignKey::generate('com_test_pinx', 'com_test_pinx:other');
    PinxSignKey::save($otherKey, $otherKeyPath);
    AppEngine::__rebuild();

    $update = pinxSystemBuild('com_test_pinx', ['sign' => true, 'sign_key' => $otherKeyPath]);

    $result = (new PinxInstaller(AppEngine::___(), SystemConfig::path('wizard_tmp')))
        ->install($update['path'], [
            'force' => true,
            'skip_migrate' => true,
            'skip_patch' => true,
            'skip_cache' => true,
        ]);

    expect($result->success)->toBeFalse()
        ->and($result->message)->toContain('signing key does not match');
});

function pinxSystemWriteTestApp(string $package, array $extra = [], bool $withTheme = false, bool $withThemeMeta = false): void
{
    $dir = pinxSystemAppDir($package);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $config = array_merge([
        'package' => $package,
        'enable' => true,
        'name' => 'Pinx Test',
        'version-name' => '1.0.1',
        'version-code' => 2,
        'theme' => 'default',
    ], $extra);

    $export = var_export($config, true);
    file_put_contents($dir . '/app.php', "<?php\n\nreturn {$export};\n");
    file_put_contents($dir . '/marker.txt', 'source');

    $theme = $withTheme ? 'spark' : 'default';
    $themeDir = $dir . '/theme/' . $theme;
    if (!is_dir($themeDir)) {
        mkdir($themeDir, 0777, true);
    }
    file_put_contents($themeDir . '/theme.txt', $theme);

    if ($withThemeMeta) {
        file_put_contents($themeDir . '/theme.php', "<?php\n\nreturn " . var_export([
            'name' => $theme,
            'package' => $package,
            'title' => ['en' => ucfirst($theme)],
            'version-name' => '1.0',
            'version-code' => 1,
        ], true) . ";\n");
    }
}

function pinxSystemDeleteTestApp(string $package): void
{
    pinxSystemDeleteDirectory(pinxSystemAppDir($package));
    pinxSystemDeleteDirectory(testProjectRoot() . '/pinker/apps/' . $package);
}

function pinxSystemDeleteDirectory(string $dir): void
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

function pinxSystemAppDir(string $package): string
{
    return testProjectRoot() . '/apps/' . $package;
}

function pinxSystemAppFile(string $package): string
{
    return pinxSystemAppDir($package) . '/app.php';
}

function pinxSystemCleanupArtifacts(): void
{
    $dir = testFixtures('pinx');
    if (!is_dir($dir)) {
        return;
    }

    foreach (glob($dir . '/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }

    foreach (['com_test_pinx', 'com_test_pinx_clone'] as $package) {
        $exportDir = pinxSystemAppDir($package) . '/export';
        if (is_dir($exportDir)) {
            pinxSystemDeleteDirectory($exportDir);
        }

        $pinxDir = pinxSystemAppDir($package) . '/pinx';
        if (is_dir($pinxDir)) {
            pinxSystemDeleteDirectory($pinxDir);
        }

        $identityDir = pinxSystemAppDir($package) . '/.pinx';
        if (is_dir($identityDir)) {
            pinxSystemDeleteDirectory($identityDir);
        }
    }
}

function pinxSystemTempFile(string $name): string
{
    $dir = testFixtures('pinx');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir . '/' . $name;
}

