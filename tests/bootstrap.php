<?php

/**
 * Shared bootstrap for framework and app tests.
 */

require_once dirname(__DIR__) . '/system/launcher/core-path.php';
require_once PINOOX_CORE_PATH . 'functions/base.php';
require_once dirname(__DIR__) . '/system/support/system_model_aliases.php';
require_once __DIR__ . '/Support/AppTestHelpers.php';
require_once __DIR__ . '/Support/ApiSystemHelpers.php';

Pinoox\Component\Test\AppTestKit::boot();
