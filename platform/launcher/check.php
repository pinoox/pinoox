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

/**
 * Pre-flight platform check (CLI or browser) before composer install / boot.
 */

require_once __DIR__ . '/requirements.php';

if (pinoox_php_version_ok()) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDOUT, 'PHP ' . PHP_VERSION . ' satisfies composer.json (>= ' . pinoox_min_php_version() . ').' . PHP_EOL);

        if (!pinoox_vendor_installed()) {
            pinoox_load_requirement_renderer();
            pinoox_load_composer_helper();
            fwrite(STDOUT, 'Project directory: ' . pinoox_project_path() . PHP_EOL);
            fwrite(STDOUT, 'Run in terminal: ' . pinoox_composer_terminal_command() . PHP_EOL);
            exit(2);
        }

        fwrite(STDOUT, 'Requirements OK.' . PHP_EOL);
        exit(0);
    }

    if (!pinoox_vendor_installed()) {
        pinoox_load_requirement_renderer();
        pinoox_render_vendor_missing_error();
        exit(1);
    }

    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Requirements OK.' . PHP_EOL;
    exit(0);
}

pinoox_check_runtime_requirements();
