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
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WallpaperHelper
{
    private const FOLDER = 'system/wallpapers';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const PREFERRED_DEFAULT = 'default';

    public static function folder(): string
    {
        $dir = path(self::FOLDER);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        return $dir;
    }

    public static function all(): array
    {
        $dir = self::folder();
        $files = [];

        foreach (self::EXTENSIONS as $ext) {
            foreach (glob($dir . '/*.' . $ext) ?: [] as $file) {
                if (!is_file($file))
                    continue;

                $item = self::itemFromFile($file);
                $files[$item['id']] = $item;
            }
        }

        ksort($files, SORT_NATURAL);

        return array_values($files);
    }

    public static function defaultId(): string
    {
        $wallpapers = self::all();
        if ($wallpapers === [])
            return '';

        foreach ($wallpapers as $wallpaper) {
            if ($wallpaper['id'] === self::PREFERRED_DEFAULT)
                return $wallpaper['id'];
        }

        return $wallpapers[0]['id'];
    }

    public static function resolve(?string $selected): string
    {
        $wallpapers = self::all();
        if ($wallpapers === [])
            return '';

        $selected = trim(basename((string) $selected));
        $selected = pathinfo($selected, PATHINFO_FILENAME);

        foreach ($wallpapers as $wallpaper) {
            if ($wallpaper['id'] === $selected)
                return $selected;
        }

        return self::defaultId();
    }

    public static function filePath(string $name): ?string
    {
        return self::findInFolder(self::folder(), pathinfo(basename($name), PATHINFO_FILENAME));
    }

    public static function exists(string $name): bool
    {
        return self::filePath($name) !== null;
    }

    public static function upload(UploadedFile $file): ?array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');
        if (!in_array($ext, self::EXTENSIONS, true))
            return null;

        $id = self::nextUploadId();
        $dir = self::folder();
        $target = $dir . '/' . $id . '.' . $ext;
        $file->move($dir, $id . '.' . $ext);

        return self::itemFromFile($target);
    }

    public static function delete(string $name): bool
    {
        $id = pathinfo(basename($name), PATHINFO_FILENAME);
        $file = self::findInFolder(self::folder(), $id);

        if (!$file)
            return false;

        return @unlink($file);
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

    private static function itemFromFile(string $file): array
    {
        $id = pathinfo($file, PATHINFO_FILENAME);

        return [
            'id' => $id,
            'file' => basename($file),
            'url' => self::url(basename($file)),
            'deletable' => true,
        ];
    }

    private static function findInFolder(string $dir, string $id): ?string
    {
        if (!is_dir($dir))
            return null;

        foreach (self::EXTENSIONS as $ext) {
            $file = $dir . '/' . $id . '.' . $ext;
            if (is_file($file))
                return $file;
        }

        return null;
    }

    private static function nextUploadId(): string
    {
        $existing = [];
        foreach (self::all() as $wallpaper)
            $existing[$wallpaper['id']] = true;

        $index = 1;
        while (isset($existing[(string) $index]))
            $index++;

        return (string) $index;
    }
}
