<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$root = devScriptProjectRoot();
$exclude = ['vendor', 'node_modules', '.git', '.idea', '.vscode', '.cursor', 'pinker'];
$invalid = [];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

    foreach ($exclude as $ex) {
        if ($rel === $ex || str_starts_with($rel, $ex . '/')) {
            continue 2;
        }
    }

    if (preg_match('#(^|/)vendor(/|$)#', $rel) === 1) {
        continue;
    }

    $bytes = @file_get_contents($file->getPathname());

    if ($bytes === false || $bytes === '') {
        continue;
    }

    if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
        $bytes = substr($bytes, 3);
    }

    if (!mb_check_encoding($bytes, 'UTF-8')) {
        $invalid[] = $rel;
    }
}

echo count($invalid) . " non-utf8 PHP file(s)\n";

foreach (array_slice($invalid, 0, 50) as $path) {
    echo $path . "\n";
}

exit($invalid === [] ? 0 : 1);
