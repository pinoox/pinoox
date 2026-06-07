<?php

use Pinoox\Component\Test\AppTestKit;

function apiSystemWriteTestApp(string $package, array $files): void
{
    AppTestKit::fakeApp($package, $files);
}

function apiSystemDeleteTestApp(string $package): void
{
    AppTestKit::deleteFakeApp($package);
}

function apiSystemDeleteDirectory(string $dir): void
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

