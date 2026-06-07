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

namespace Pinoox\Component\Helpers;

use Pinoox\Component\Kernel\Loader;

class EnvFile
{
    public function __construct(private readonly string $path)
    {
    }

    public static function forProject(?string $basePath = null): self
    {
        $base = $basePath ?? Loader::getBasePath();
        $root = is_string($base) && $base !== '' ? rtrim(str_replace('\\', '/', $base), '/') : getcwd();

        return new self($root . '/.env');
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    public function setMany(array $variables): bool
    {
        $content = $this->exists() ? (string) @file_get_contents($this->path) : $this->defaultTemplate();

        foreach ($variables as $key => $value) {
            $content = $this->setLine($content, (string) $key, $this->encode($value));
        }

        $directory = dirname($this->path);

        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        return @file_put_contents($this->path, $content, LOCK_EX) !== false;
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    public function applyToRuntime(array $variables): void
    {
        foreach ($variables as $key => $value) {
            $encoded = $this->encode($value);
            $_ENV[$key] = $encoded;
            $_SERVER[$key] = $encoded;
            putenv($key . '=' . $encoded);
        }
    }

    private function defaultTemplate(): string
    {
        $example = dirname($this->path) . '/.env.example';

        if (is_file($example)) {
            return (string) @file_get_contents($example);
        }

        return "# Pinoox Environment\n";
    }

    private function setLine(string $content, string $key, string $value): string
    {
        $line = $key . '=' . $value;
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, $line, $content);
        }

        $content = rtrim($content, "\r\n");

        return $content === '' ? $line . "\n" : $content . "\n" . $line . "\n";
    }

    private function encode(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/[\s#"\']/', $value) === 1) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        return $value;
    }
}

