<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

require_once __DIR__ . '/requirements.php';
require_once __DIR__ . '/composer-helper.php';

header('Cache-Control: no-store, no-cache, must-revalidate');

if (!pinoox_php_version_ok()) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'php_version']);
    exit;
}

if (pinoox_vendor_installed()) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => true, 'vendor_installed' => true]);
    exit;
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'status');

if ($action === 'install-stream') {
    @set_time_limit(0);
    @ignore_user_abort(true);
    @ini_set('max_execution_time', '0');
    @ini_set('implicit_flush', '1');

    header('Content-Type: application/x-ndjson; charset=UTF-8');
    header('X-Accel-Buffering: no');

    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }

    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', '0');

    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    pinoox_stream_composer_install(static function (array $payload): void {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();
    });

    exit;
}

header('Content-Type: application/json; charset=UTF-8');

if ($action === 'install') {
    $result = pinoox_run_composer_install();
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);
    exit;
}

echo json_encode(pinoox_composer_status_payload());
