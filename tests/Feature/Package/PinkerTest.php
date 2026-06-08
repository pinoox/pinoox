<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Pinker;

beforeEach(function () {
    Loader::setBasePath(testProjectRoot());
    AppProvider::___();
    deletePinkerTestApp('com_test_pinker');
});

afterEach(function () {
    deletePinkerTestApp('com_test_pinker');
});

it('stores app baked files in the project-level pinker directory', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    file_put_contents($appDir . '/app.php', "<?php\n\nreturn ['package' => '{$package}', 'name' => 'Pinker Test'];\n");

    $pinker = Pinker::folder($appDir, 'app.php');
    $data = $pinker->pickup();
    $status = $pinker->status();

    expect($pinker->getBakedFile())->toBe($basePath . '/pinker/apps/' . $package . '/app.php')
        ->and($data['package'])->toBe($package)
        ->and($status['source_size'])->toBe(filesize($appDir . '/app.php'))
        ->and(is_file($basePath . '/pinker/apps/' . $package . '/app.php'))->toBeTrue()
        ->and(is_dir($appDir . '/pinker'))->toBeFalse();
});

it('refreshes the cache when the source file changes', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    file_put_contents($appDir . '/app.php', "<?php\n\nreturn ['name' => 'Before'];\n");

    $pinker = Pinker::folder($appDir, 'app.php');

    expect($pinker->pickup()['name'])->toBe('Before');

    sleep(1);
    file_put_contents($appDir . '/app.php', "<?php\n\nreturn ['name' => 'After'];\n");

    expect($pinker->pickup()['name'])->toBe('After');
});

it('keeps env sensitive source files out of the cache', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    file_put_contents($appDir . '/app.php', "<?php\n\nreturn ['debug' => env('PINKER_TEST_DEBUG', false)];\n");

    $pinker = Pinker::folder($appDir, 'app.php');
    $pinker->pickup();

    expect(is_file($pinker->getBakedFile()))->toBeFalse();
});

it('stores runtime config changes as state overrides', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    file_put_contents($appDir . '/app.php', "<?php\n\nreturn ['name' => 'Source', 'nested' => ['keep' => true, 'remove' => true]];\n");

    $pinker = Pinker::folder($appDir, 'app.php');
    $data = $pinker->pickup();
    unset($data['nested']['remove']);
    $data['name'] = 'Runtime';

    $pinker->data($data)->bake();

    expect($pinker->pickup())
        ->toMatchArray(['name' => 'Runtime', 'nested' => ['keep' => true]])
        ->and($pinker->getBakedFile())->toBe($basePath . '/pinker/apps/' . $package . '/app.php')
        ->and($pinker->getOverrideFile())->toBe($basePath . '/pinker/state/apps/' . $package . '/app.php')
        ->and(is_file($pinker->getOverrideFile()))->toBeTrue();
});

it('bakes closures in app config files', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    file_put_contents($appDir . '/app.php', <<<'PHP'
<?php

return [
    'package' => 'com_test_pinker',
    'startup' => function () {
        return 'ready';
    },
    'hooks' => [
        'nested' => function (array $payload = []) {
            return $payload['value'] ?? null;
        },
    ],
];
PHP);

    $pinker = Pinker::folder($appDir, 'app.php')->dumping(true);
    $data = $pinker->pickup();

    expect($data['startup'])->toBeInstanceOf(Closure::class)
        ->and($data['startup']())->toBe('ready')
        ->and($data['hooks']['nested'](['value' => 'ok']))->toBe('ok');
});

it('recovers when the baked cache file is corrupted', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    $sourceFile = $appDir . '/app.php';
    file_put_contents($sourceFile, "<?php\n\nreturn ['name' => 'Recovered'];\n");

    $pinker = Pinker::folder($appDir, 'app.php');
    $bakedFile = $pinker->getBakedFile();

    if (!is_dir(dirname($bakedFile))) {
        mkdir(dirname($bakedFile), 0777, true);
    }

    file_put_contents($bakedFile, "<?php\n/**\n * Pinoox Baker\n * @time " . time() . "\n * @schema 2\n * @source {$sourceFile}\n * @source_hash " . sha1_file($sourceFile) . "\n * @source_mtime " . filemtime($sourceFile) . "\n * @source_size " . filesize($sourceFile) . "\n * @env_sensitive no\n */\n\nreturn [");

    expect($pinker->pickup()['name'])->toBe('Recovered')
        ->and($pinker->status()['cache_valid'])->toBeTrue();
});

it('maps pincore config source files to pinker/config', function () {
    $basePath = pinkerTestPath(testProjectRoot());
    $corePath = defined('PINOOX_CORE_PATH')
        ? pinkerTestPath(PINOOX_CORE_PATH)
        : $basePath . '/pincore/';
    $sourceFile = pinkerTestPath($corePath . 'config/app/source.config.php');

    expect(Pinker::bakedFileFromSource($sourceFile))
        ->toBe($basePath . '/pinker/config/app/source.config.php');
});

function deletePinkerTestApp(string $package): void
{
    deletePinkerTestDirectory(testProjectRoot() . '/apps/' . $package);
    deletePinkerTestDirectory(testProjectRoot() . '/pinker/apps/' . $package);
    deletePinkerTestDirectory(testProjectRoot() . '/pinker/state/apps/' . $package);
}

function pinkerTestPath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function deletePinkerTestDirectory(string $dir): void
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

