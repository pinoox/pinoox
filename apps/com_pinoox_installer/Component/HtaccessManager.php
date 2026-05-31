<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_installer\Component;

class HtaccessManager
{
    private const MARKER_BEGIN = '# BEGIN pinoox';
    private const MARKER_END = '# END pinoox';

    public function defaultContent(): string
    {
        return <<<'HTACCESS'
# BEGIN pinoox

<IfModule mod_rewrite.c>
RewriteEngine On

# Check if the environment is local or not
# RewriteCond %{HTTP_HOST} ^(localhost|127\.0\.0\.1) [NC]
# RewriteRule ^ - [S=1]

# Redirect to HTTPS if not local
# RewriteCond %{HTTPS} off
# RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R,L]

# Skip index.php direct access rule
RewriteRule ^index\.php$ - [L]

# Route all requests to index.php if file or directory doesn't exist
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* index.php [L]

</IfModule>
# END pinoox

HTACCESS;
    }

    public function getPath(): string
    {
        if (function_exists('path')) {
            $root = @path('~');

            if (is_string($root) && $root !== '' && is_dir($root)) {
                return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';
            }
        }

        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.htaccess';
    }

    public function status(): array
    {
        $path = $this->getPath();
        $exists = is_file($path);
        $content = $exists ? @file_get_contents($path) : false;
        $isEmpty = !$exists || $content === false || trim((string) $content) === '';
        $hasPinoox = is_string($content) && str_contains($content, self::MARKER_BEGIN);
        $writable = $this->isWritable($path, $exists);

        return [
            'exists' => $exists,
            'empty' => $isEmpty,
            'has_pinoox' => $hasPinoox,
            'writable' => $writable,
            'can_create' => $writable && ($isEmpty || !$exists),
            'content' => $this->defaultContent(),
        ];
    }

    public function create(): array
    {
        $path = $this->getPath();
        $exists = is_file($path);
        $content = $exists ? @file_get_contents($path) : false;
        $isEmpty = !$exists || $content === false || trim((string) $content) === '';

        if ($exists && is_string($content) && str_contains($content, self::MARKER_BEGIN)) {
            return [
                'ok' => true,
                'created' => false,
                'state' => 'exists',
            ];
        }

        if ($exists && !$isEmpty) {
            return [
                'ok' => false,
                'created' => false,
                'state' => 'occupied',
            ];
        }

        if (!$this->isWritable($path, $exists)) {
            return [
                'ok' => false,
                'created' => false,
                'state' => 'not_writable',
            ];
        }

        $written = @file_put_contents($path, $this->defaultContent(), LOCK_EX);

        if ($written === false) {
            return [
                'ok' => false,
                'created' => false,
                'state' => 'write_failed',
            ];
        }

        return [
            'ok' => true,
            'created' => true,
            'state' => 'created',
            'content' => $this->defaultContent(),
        ];
    }

    private function isWritable(string $path, bool $exists): bool
    {
        if ($exists) {
            return is_writable($path);
        }

        $directory = dirname($path);

        return is_dir($directory) && is_writable($directory);
    }
}
