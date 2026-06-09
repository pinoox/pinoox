<?php

/**
 * @deprecated Use vendor/pinoox/pincore/launcher/test-paths.php — kept for platform path compatibility.
 */

$coreLauncher = dirname(__DIR__) . '/vendor/pinoox/pincore/launcher/test-paths.php';

if (!is_file($coreLauncher)) {
    throw new RuntimeException('Pinoox core launcher test-paths.php was not found. Run composer install.');
}

require_once $coreLauncher;
