<?php

/**
 * Shared bootstrap for framework and app tests.
 */

require_once dirname(__DIR__) . '/launcher/core-path.php';
require_once PINOOX_CORE_PATH . 'functions/base.php';
require_once __DIR__ . '/Support/AppTestHelpers.php';
require_once __DIR__ . '/Support/ApiSystemHelpers.php';
require_once __DIR__ . '/Support/InstallerTestHelpers.php';
require_once __DIR__ . '/Support/DatabaseTestHelpers.php';

require_once __DIR__ . '/Support/TestSandbox.php';

\Pinoox\Component\Helpers\EnvBootstrap::load(PINOOX_BASE_PATH);

// PHPUnit/Pest: test runtime overrides machine env (individual tests may override again).
putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';
putenv('DB_CONNECTION=sqlite');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_CONNECTION'] = 'sqlite';

Pinoox\Component\Test\AppTestKit::boot();
