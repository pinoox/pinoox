<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Store\Baker;

use Pinoox\Component\File;
use Throwable;

/**
 * Class to handle file operations
 */
/**
 * Class to handle file operations
 */
class FileHandler implements FileHandlerInterface
{

    private const LOCK_TIMEOUT_MICROSECONDS = 3000000;

    private const LOCK_SLEEP_MICROSECONDS = 50000;

    public function store(string $file, $data): void
    {
        $folder = dirname($file);

        if (!is_dir($folder)) {
            File::make_folder($folder, true, 0777, false);
        }

        $lockFile = $file . '.lock';
        $lock = $this->lock($lockFile);

        try {
            $this->atomicStore($file, (string)$data);
        } finally {
            $this->unlock($lock, $lockFile);
        }
    }

    public function retrieve(string $file)
    {
        if (is_file($file)) {
            try {
                return include $file;
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    public function remove(string $file): void
    {
        File::remove_file($file);
    }

    private function atomicStore(string $file, string $data): void
    {
        $tmpFile = $file . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $handle = @fopen($tmpFile, 'wb');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open temporary Pinker file: {$tmpFile}");
        }

        try {
            if (@fwrite($handle, $data) === false) {
                throw new \RuntimeException("Unable to write temporary Pinker file: {$tmpFile}");
            }

            fflush($handle);
        } finally {
            fclose($handle);
        }

        if (!@rename($tmpFile, $file)) {
            if (is_file($file)) {
                @unlink($file);
            }

            if (!@rename($tmpFile, $file)) {
                @unlink($tmpFile);
                throw new \RuntimeException("Unable to publish Pinker file: {$file}");
            }
        }
    }

    /**
     * @return resource
     */
    private function lock(string $lockFile)
    {
        $handle = @fopen($lockFile, 'c');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open Pinker lock file: {$lockFile}");
        }

        $start = microtime(true);

        do {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                return $handle;
            }

            usleep(self::LOCK_SLEEP_MICROSECONDS);
        } while (((microtime(true) - $start) * 1000000) < self::LOCK_TIMEOUT_MICROSECONDS);

        fclose($handle);
        throw new \RuntimeException("Timed out waiting for Pinker lock: {$lockFile}");
    }

    /**
     * @param resource $handle
     */
    private function unlock($handle, string $lockFile): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
        @unlink($lockFile);
    }

}

