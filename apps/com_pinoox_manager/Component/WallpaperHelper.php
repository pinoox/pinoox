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

namespace App\com_pinoox_manager\Component;

use Pinoox\Portal\Url;

class WallpaperHelper
{
    private const FOLDER = 'system/wallpapers';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const PREFERRED_DEFAULT = 'default';

    public static function folder(): string
    {
        return path(self::FOLDER);
    }

    public static function all(): array
    {
        $dir = self::folder();
        if (!is_dir($dir))
            return [];

        $files = [];
        foreach (self::EXTENSIONS as $ext) {
            foreach (glob($dir . '/*.' . $ext) ?: [] as $file) {
                if (!is_file($file))
                    continue;

                $id = pathinfo($file, PATHINFO_FILENAME);
                $files[$id] = [
                    'id' => $id,
                    'file' => basename($file),
                    'url' => self::url(basename($file)),
                ];
            }
        }

        ksort($files, SORT_NATURAL);

        return array_values($files);
    }

    public static function defaultId(): string
    {
        $wallpapers = self::all();
        if ($wallpapers === [])
            return '1';

        foreach ($wallpapers as $wallpaper) {
            if ($wallpaper['id'] === self::PREFERRED_DEFAULT)
                return $wallpaper['id'];
        }

        return $wallpapers[0]['id'];
    }

    public static function resolve(?string $selected): string
    {
        $selected = trim(basename((string) $selected));
        $selected = pathinfo($selected, PATHINFO_FILENAME);

        foreach (self::all() as $wallpaper) {
            if ($wallpaper['id'] === $selected)
                return $selected;
        }

        return self::defaultId();
    }

    public static function filePath(string $name): ?string
    {
        $name = basename($name);
        $id = pathinfo($name, PATHINFO_FILENAME);
        $dir = self::folder();

        if (!is_dir($dir))
            return null;

        foreach (self::EXTENSIONS as $ext) {
            $file = $dir . '/' . $id . '.' . $ext;
            if (is_file($file))
                return $file;
        }

        return null;
    }

    public static function exists(string $name): bool
    {
        return self::filePath($name) !== null;
    }

    public static function url(string $fileName): string
    {
        return Url::get('api/v1/wallpapers/' . rawurlencode(basename($fileName)));
    }

    public static function mimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
