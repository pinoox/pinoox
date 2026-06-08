<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$base = devScriptProjectRoot() . '/apps/com_pinoox_installer/lang/fa';
$failed = false;

foreach (glob($base . '/*.lang.php') ?: [] as $file) {
    $name = basename($file);
    $data = include $file;

    try {
        json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        echo $name . ": ok\n";
    } catch (Throwable $e) {
        echo $name . ': ' . $e->getMessage() . "\n";
        $failed = true;

        array_walk_recursive($data, static function (mixed $value, mixed $key) use ($name): void {
            if (!is_string($value)) {
                return;
            }

            if (!mb_check_encoding($value, 'UTF-8')) {
                echo "  invalid key={$key} value=" . substr($value, 0, 40) . "\n";
            }
        });
    }
}

exit($failed ? 1 : 0);
