<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$files = array_slice($argv, 1);

if ($files === []) {
    fwrite(STDERR, "Usage: php tests/bin/dev/utf8-check-files.php <relative-path> [...]\n");
    exit(1);
}

$root = devScriptProjectRoot();
$failed = false;

foreach ($files as $rel) {
    $path = $root . '/' . str_replace('\\', '/', $rel);

    if (!is_file($path)) {
        echo "{$rel}: MISSING\n";
        $failed = true;
        continue;
    }

    $content = file_get_contents($path);
    $valid = mb_check_encoding($content, 'UTF-8') ? 'yes' : 'NO';

    try {
        json_encode(['sample' => $content], JSON_THROW_ON_ERROR);
        $json = 'ok';
    } catch (Throwable $e) {
        $json = $e->getMessage();
        $failed = true;
    }

    echo "{$rel}: utf8={$valid} json={$json}\n";
}

exit($failed ? 1 : 0);
