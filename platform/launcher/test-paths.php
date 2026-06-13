<?php

/**
 * @deprecated Use pincore/launcher/test-paths.php — kept for platform path compatibility.
 */

require_once __DIR__ . '/core-path.php';

$coreRoot = rtrim(pinoox_resolve_configured_core_path(dirname(__DIR__, 2)), '/');
$coreLauncher = $coreRoot . '/launcher/test-paths.php';

if (!is_file($coreLauncher)) {
    throw new RuntimeException('Pinoox core launcher test-paths.php was not found. Run composer install or clone pincore into pincore/.');
}

require_once $coreLauncher;
