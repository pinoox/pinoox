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

function pinoox_project_path(): string
{
    return pinoox_base_path();
}

function pinoox_public_core_path(): string
{
    $corePath = pinoox_core_path();
    $basePath = pinoox_base_path();

    if (str_starts_with($corePath, $basePath . '/')) {
        return ltrim(substr($corePath, strlen($basePath)), '/');
    }

    return 'pincore';
}

function pinoox_composer_terminal_command(): string
{
    $path = pinoox_project_path();

    if (PHP_OS_FAMILY === 'Windows') {
        return 'cd /d "' . $path . '" && composer install';
    }

    return 'cd "' . $path . '" && composer install';
}

function pinoox_composer_action_url(): string
{
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $base = rtrim($scriptDir, '/');

    return $base . '/system/launcher/composer-action.php';
}

function pinoox_shell_functions_available(): bool
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

    foreach (['proc_open', 'shell_exec', 'exec', 'popen'] as $function) {
        if (function_exists($function) && !in_array($function, $disabled, true)) {
            return true;
        }
    }

    return false;
}

function pinoox_run_command(
    string $command,
    ?string $cwd = null,
    ?callable $onOutput = null,
    ?callable $onHeartbeat = null
): array {
    $cwd = $cwd ?? pinoox_project_path();
    $output = '';
    $exitCode = 1;

    if (function_exists('proc_open')) {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (!in_array('proc_open', $disabled, true)) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($command, $descriptors, $pipes, $cwd, null, ['bypass_shell' => false]);

            if (is_resource($process)) {
                fclose($pipes[0]);
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);

                $lastOutputAt = time();

                $drain = static function ($pipe) use (&$output, $onOutput, &$lastOutputAt): void {
                    if (!is_resource($pipe)) {
                        return;
                    }

                    while (($chunk = fread($pipe, 8192)) !== false && $chunk !== '') {
                        $output .= $chunk;
                        $lastOutputAt = time();

                        if ($onOutput !== null) {
                            $onOutput($chunk);
                        }
                    }
                };

                while (true) {
                    if (isset($pipes[1])) {
                        $drain($pipes[1]);
                    }

                    if (isset($pipes[2])) {
                        $drain($pipes[2]);
                    }

                    if ($onHeartbeat !== null && (time() - $lastOutputAt) >= 2) {
                        $lastOutputAt = time();
                        $onHeartbeat();
                    }

                    $status = proc_get_status($process);

                    if (!$status['running']) {
                        foreach ([1, 2] as $index) {
                            if (!isset($pipes[$index])) {
                                continue;
                            }

                            stream_set_blocking($pipes[$index], true);
                            stream_set_timeout($pipes[$index], 2);
                            $drain($pipes[$index]);
                            fclose($pipes[$index]);
                            unset($pipes[$index]);
                        }

                        break;
                    }

                    usleep(150000);
                }

                $exitCode = proc_close($process);

                return [
                    'exit_code' => $exitCode,
                    'output' => trim($output),
                ];
            }
        }
    }

    if ($onOutput === null && function_exists('shell_exec')) {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (!in_array('shell_exec', $disabled, true)) {
            $result = @shell_exec($command . ' 2>&1');

            return [
                'exit_code' => $result === null ? 1 : 0,
                'output' => trim((string) $result),
            ];
        }
    }

    return [
        'exit_code' => 1,
        'output' => '',
    ];
}

function pinoox_composer_candidate_commands(): array
{
    $base = pinoox_project_path();
    $candidates = [];

    if (PHP_OS_FAMILY === 'Windows') {
        $candidates[] = ['command' => 'composer', 'label' => 'composer'];
        $candidates[] = ['command' => 'composer.bat', 'label' => 'composer.bat'];

        $localAppData = getenv('LOCALAPPDATA') ?: '';
        $appData = getenv('APPDATA') ?: '';

        foreach ([
            $localAppData . '\\Programs\\Composer\\composer.bat',
            $appData . '\\Composer\\vendor\\bin\\composer.bat',
            'C:\\ProgramData\\ComposerSetup\\bin\\composer.bat',
        ] as $path) {
            if ($path !== '\\Programs\\Composer\\composer.bat' && is_file($path)) {
                $candidates[] = ['command' => '"' . $path . '"', 'label' => $path];
            }
        }
    } else {
        $candidates[] = ['command' => 'composer', 'label' => 'composer'];

        foreach (['/usr/local/bin/composer', '/usr/bin/composer'] as $path) {
            if (is_file($path)) {
                $candidates[] = ['command' => escapeshellarg($path), 'label' => $path];
            }
        }
    }

    $phar = $base . '/composer.phar';

    if (is_file($phar)) {
        $php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $candidates[] = [
            'command' => escapeshellarg($php) . ' ' . escapeshellarg($phar),
            'label' => 'composer.phar',
        ];
    }

    return $candidates;
}

function pinoox_detect_composer(): array
{
    if (!pinoox_shell_functions_available()) {
        return [
            'installed' => false,
            'command' => null,
            'version' => null,
            'source' => null,
            'shell_available' => false,
        ];
    }

    foreach (pinoox_composer_candidate_commands() as $candidate) {
        $result = pinoox_run_command($candidate['command'] . ' --version 2>&1');

        if ($result['exit_code'] !== 0 || $result['output'] === '') {
            continue;
        }

        if (stripos($result['output'], 'composer') === false) {
            continue;
        }

        return [
            'installed' => true,
            'command' => $candidate['command'],
            'version' => trim($result['output']),
            'source' => $candidate['label'],
            'shell_available' => true,
        ];
    }

    return [
        'installed' => false,
        'command' => null,
        'version' => null,
        'source' => null,
        'shell_available' => true,
    ];
}

function pinoox_composer_status_payload(): array
{
    $composer = pinoox_detect_composer();

    return [
        'ok' => true,
        'vendor_installed' => pinoox_vendor_installed(),
        'project_path' => pinoox_project_path(),
        'terminal_command' => pinoox_composer_terminal_command(),
        'php_version' => PHP_VERSION,
        'composer' => $composer,
        'composer_download_url' => 'https://getcomposer.org/download/',
    ];
}

function pinoox_composer_install_shell_command(?array $composer = null): ?string
{
    $composer = $composer ?? pinoox_detect_composer();

    if (!$composer['installed'] || $composer['command'] === null) {
        return null;
    }

    return $composer['command'] . ' install --no-interaction --no-ansi --verbose --prefer-dist 2>&1';
}

function pinoox_emit_composer_stream_event(callable $emit, array $payload): void
{
    $emit($payload);
}

function pinoox_stream_composer_install(callable $emit): void
{
    if (pinoox_vendor_installed()) {
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'done',
            'success' => true,
            'message' => 'already_installed',
            'exit_code' => 0,
        ]);

        return;
    }

    if (!pinoox_php_version_ok()) {
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'error',
            'message' => 'php_version',
        ]);
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'done',
            'success' => false,
            'message' => 'php_version',
            'exit_code' => 1,
        ]);

        return;
    }

    $composer = pinoox_detect_composer();

    if (!$composer['shell_available']) {
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'error',
            'message' => 'shell_disabled',
        ]);
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'done',
            'success' => false,
            'message' => 'shell_disabled',
            'exit_code' => 1,
        ]);

        return;
    }

    if (!$composer['installed'] || $composer['command'] === null) {
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'error',
            'message' => 'composer_missing',
        ]);
        pinoox_emit_composer_stream_event($emit, [
            'type' => 'done',
            'success' => false,
            'message' => 'composer_missing',
            'exit_code' => 1,
        ]);

        return;
    }

    $command = pinoox_composer_install_shell_command($composer);
    $cwd = pinoox_project_path();

    pinoox_emit_composer_stream_event($emit, [
        'type' => 'start',
        'command' => $command,
        'cwd' => $cwd,
        'display_command' => pinoox_composer_terminal_command(),
    ]);

    $result = pinoox_run_command(
        $command,
        $cwd,
        static function (string $chunk) use ($emit): void {
            pinoox_emit_composer_stream_event($emit, [
                'type' => 'output',
                'text' => str_replace(["\r\n", "\r"], "\n", $chunk),
            ]);
        },
        static function () use ($emit): void {
            pinoox_emit_composer_stream_event($emit, [
                'type' => 'heartbeat',
            ]);
        }
    );

    $success = $result['exit_code'] === 0 && pinoox_vendor_installed();

    pinoox_emit_composer_stream_event($emit, [
        'type' => 'done',
        'success' => $success,
        'message' => $success ? 'installed' : 'install_failed',
        'exit_code' => $result['exit_code'],
        'output' => $result['output'],
    ]);
}

function pinoox_run_composer_install(): array
{
    if (pinoox_vendor_installed()) {
        return [
            'ok' => true,
            'success' => true,
            'message' => 'vendor already installed',
            'output' => '',
        ];
    }

    if (!pinoox_php_version_ok()) {
        return [
            'ok' => false,
            'success' => false,
            'message' => 'php_version',
            'output' => '',
        ];
    }

    $composer = pinoox_detect_composer();

    if (!$composer['shell_available']) {
        return [
            'ok' => false,
            'success' => false,
            'message' => 'shell_disabled',
            'output' => '',
        ];
    }

    if (!$composer['installed'] || $composer['command'] === null) {
        return [
            'ok' => false,
            'success' => false,
            'message' => 'composer_missing',
            'output' => '',
        ];
    }

    $command = pinoox_composer_install_shell_command($composer);

    if ($command === null) {
        return [
            'ok' => false,
            'success' => false,
            'message' => 'composer_missing',
            'output' => '',
        ];
    }

    $result = pinoox_run_command($command);

    if ($result['exit_code'] === 0 && pinoox_vendor_installed()) {
        return [
            'ok' => true,
            'success' => true,
            'message' => 'installed',
            'output' => $result['output'],
        ];
    }

    return [
        'ok' => false,
        'success' => false,
        'message' => 'install_failed',
        'output' => $result['output'],
    ];
}
