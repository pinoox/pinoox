<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\FileSystem;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    AppProvider::___();
    deletePortalFileSystemTestDirectory(portalFileSystemTestDir());
});

afterEach(function () {
    deletePortalFileSystemTestDirectory(portalFileSystemTestDir());
});

it('declares the FileSystem portal contract', function () {
    expectPortalContract(FileSystem::class);
});

it('forwards filesystem calls', function () {
    $dir = portalFileSystemTestDir();
    $file = $dir . '/sample.txt';

    FileSystem::mkdir($dir);
    FileSystem::dumpFile($file, 'portal-ok');

    expect(FileSystem::___())->toBeInstanceOf(\Symfony\Component\Filesystem\Filesystem::class)
        ->and(FileSystem::exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toBe('portal-ok');
});

function portalFileSystemTestDir(): string
{
    return str_replace('\\', '/', dirname(__DIR__, 3) . '/tests/Fixtures/portal_file_system');
}

function deletePortalFileSystemTestDirectory(string $dir): void
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
