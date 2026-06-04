<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Pinker;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 2));
    AppProvider::___();
    deletePinkerTestApp('com_test_pinker');
});

afterEach(function () {
    deletePinkerTestApp('com_test_pinker');
});

it('stores app baked files in the project-level pinker directory', function () {
    $basePath = pinkerTestPath(dirname(__DIR__, 2));
    $package = 'com_test_pinker';
    $appDir = $basePath . '/apps/' . $package;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0777, true);
    }

    file_put_contents($appDir . '/app.php', "<?php\n\nreturn ['package' => '{$package}', 'name' => 'Pinker Test'];\n");

    $pinker = Pinker::folder($appDir, 'app.php');
    $data = $pinker->pickup();

    expect($pinker->getBakedFile())->toBe($basePath . '/pinker/apps/' . $package . '/app.php')
        ->and($data['package'])->toBe($package)
        ->and(is_file($basePath . '/pinker/apps/' . $package . '/app.php'))->toBeTrue()
        ->and(is_dir($appDir . '/pinker'))->toBeFalse();
});

it('maps pincore source files to the project-level pincore pinker path', function () {
    $basePath = pinkerTestPath(dirname(__DIR__, 2));
    $sourceFile = $basePath . '/pincore/config/app/source.config.php';

    expect(Pinker::bakedFileFromSource($sourceFile))
        ->toBe($basePath . '/pinker/pincore/config/app/source.config.php');
});

function deletePinkerTestApp(string $package): void
{
    deletePinkerTestDirectory(dirname(__DIR__, 2) . '/apps/' . $package);
    deletePinkerTestDirectory(dirname(__DIR__, 2) . '/pinker/apps/' . $package);
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
