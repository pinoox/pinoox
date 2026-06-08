<?php

/**
 * Normalize project PHP sources to UTF-8 without BOM.
 *
 * Usage: php tests/bin/dev/normalize-php-encoding.php [--dry-run]
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$root = devScriptProjectRoot();
$dryRun = in_array('--dry-run', $argv ?? [], true);

$excludeDirs = [
    'vendor',
    'node_modules',
    '.git',
    '.idea',
    '.vscode',
    '.cursor',
    'pinker',
];

$stats = [
    'scanned' => 0,
    'bom_removed' => 0,
    'converted' => 0,
    'unchanged' => 0,
    'skipped_binary' => 0,
    'errors' => 0,
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $relative = devScriptRelativePath($file->getPathname(), $root);

    if (devScriptIsExcluded($relative, $excludeDirs)) {
        continue;
    }

    $stats['scanned']++;

    try {
        [$bom, $converted, $skipped] = devScriptNormalizePhpFile($file->getPathname(), $dryRun, $relative);
    } catch (Throwable $e) {
        $stats['errors']++;
        fwrite(STDERR, "ERROR: {$relative} — {$e->getMessage()}\n");
        continue;
    }

    if ($skipped) {
        $stats['skipped_binary']++;
        continue;
    }

    if ($bom) {
        $stats['bom_removed']++;
    }

    if ($converted) {
        $stats['converted']++;
    }

    if (!$bom && !$converted) {
        $stats['unchanged']++;
    }
}

echo 'Scanned: ' . $stats['scanned'] . PHP_EOL;
echo 'BOM removed: ' . $stats['bom_removed'] . PHP_EOL;
echo 'Encoding converted: ' . $stats['converted'] . PHP_EOL;
echo 'Unchanged: ' . $stats['unchanged'] . PHP_EOL;
echo 'Skipped (binary): ' . $stats['skipped_binary'] . PHP_EOL;
echo 'Errors: ' . $stats['errors'] . PHP_EOL;

if ($dryRun) {
    echo '(dry run — no files modified)' . PHP_EOL;
}

function devScriptNormalizePhpFile(string $path, bool $dryRun, string $relative): array
{
    $bytes = file_get_contents($path);

    if ($bytes === false) {
        throw new RuntimeException('cannot read file');
    }

    if ($bytes !== '' && strpos($bytes, "\0") !== false) {
        return [false, false, true];
    }

    $hadBom = str_starts_with($bytes, "\xEF\xBB\xBF");
    $content = $hadBom ? substr($bytes, 3) : $bytes;
    $converted = false;

    if ($content !== '' && !mb_check_encoding($content, 'UTF-8')) {
        $detected = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-6', 'ASCII'], true) ?: 'ISO-8859-1';
        $content = mb_convert_encoding($content, 'UTF-8', $detected);
        $converted = true;
    }

    if (!$hadBom && !$converted) {
        return [false, false, false];
    }

    if ($dryRun) {
        fwrite(STDOUT, ($hadBom ? 'BOM ' : 'ENC ') . $relative . PHP_EOL);

        return [$hadBom, $converted, false];
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('cannot write file');
    }

    return [$hadBom, $converted, false];
}

function devScriptRelativePath(string $path, string $root): string
{
    $path = str_replace('\\', '/', $path);
    $root = rtrim(str_replace('\\', '/', $root), '/');

    return ltrim(substr($path, strlen($root)), '/');
}

function devScriptIsExcluded(string $relative, array $excludeDirs): bool
{
    foreach ($excludeDirs as $exclude) {
        if ($relative === $exclude || str_starts_with($relative, $exclude . '/')) {
            return true;
        }
    }

    return preg_match('#/vendor(/|$)#', $relative) === 1;
}
