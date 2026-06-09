<?php

/**
 * Shared helpers for Database feature tests — avoids duplicate global functions across test files.
 */

function writeTestApp(string $package, array $config): void
{
    $dir = testProjectRoot() . '/apps/' . $package;

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $config = [
        'package' => $package,
        'enable' => true,
        'name' => $package,
        ...$config,
    ];

    file_put_contents($dir . '/app.php', "<?php\n\nreturn " . var_export($config, true) . ";\n");
}

function deleteTestApp(string $package): void
{
    deleteDirectory(testProjectRoot() . '/apps/' . $package);
    deleteDirectory(testProjectRoot() . '/pinker/apps/' . $package);
    deleteDirectory(testProjectRoot() . '/pinker/state/apps/' . $package);
}

function deleteDirectory(string $dir): void
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
