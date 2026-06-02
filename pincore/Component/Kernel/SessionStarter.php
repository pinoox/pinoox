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

namespace Pinoox\Component\Kernel;

use Symfony\Component\HttpFoundation\Request;

final class SessionStarter
{
    private static bool $savePathConfigured = false;

    public static function configureSavePath(): void
    {
        if (self::$savePathConfigured) {
            return;
        }

        self::$savePathConfigured = true;

        $current = trim((string) ini_get('session.save_path'));

        if ($current !== '' && self::isWritableDirectory($current)) {
            return;
        }

        foreach (self::candidateSavePaths() as $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }

            if (self::isWritableDirectory($path)) {
                ini_set('session.save_path', $path);
                return;
            }
        }
    }

    public static function start(Request $request): bool
    {
        if (PHP_SAPI === 'cli' || !$request->hasSession()) {
            return false;
        }

        $session = $request->getSession();

        if ($session->isStarted()) {
            return true;
        }

        if (\PHP_SESSION_ACTIVE === session_status()) {
            return false;
        }

        if (headers_sent()) {
            return false;
        }

        self::configureSavePath();

        try {
            return $session->start();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Release the session file lock as soon as the response is ready.
     * Prevents parallel requests from blocking on session_start().
     */
    public static function release(Request $request): void
    {
        if (PHP_SAPI === 'cli' || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            return;
        }

        try {
            $session->save();
        } catch (\Throwable) {
        }
    }

    /**
     * @return string[]
     */
    private static function candidateSavePaths(): array
    {
        $paths = [];

        $mampTmp = getenv('TEMP') ?: getenv('TMP') ?: '';

        if ($mampTmp !== '') {
            $paths[] = rtrim(str_replace('\\', '/', $mampTmp), '/') . '/pinoox-sessions';
        }

        $systemTemp = sys_get_temp_dir();

        if ($systemTemp !== '') {
            $paths[] = rtrim($systemTemp, '\\/') . DIRECTORY_SEPARATOR . 'pinoox-sessions';
        }

        return array_values(array_unique($paths));
    }

    private static function isWritableDirectory(string $path): bool
    {
        return is_dir($path) && is_writable($path);
    }
}
