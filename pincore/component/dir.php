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

namespace pinoox\component;

use pinoox\component\Helpers\HelperString;
use pinoox\component\kernel\Loader;
use pinoox\portal\app\App       ;

class Dir
{
    /**
     * Folder current theme
     *
     * @var string|null
     */
    private static $theme = null;

    /**
     * Path directory theme
     *
     * @var string|null
     */
    private static $pathTheme = null;

    /**
     * Set path directory & current folder theme
     *
     * @param string $theme
     * @param string|null $path
     */
    public static function setTheme($theme, $path = null)
    {
        self::$theme = $theme;
        self::$pathTheme = $path;
    }

    /**
     * Get path themes
     *
     * @param string|null $url
     * @param string|null $theme
     * @param string|null $path
     * @return string
     */
    public static function theme($url = null, $theme = null, $path = null)
    {
        if (empty($theme))
            $theme = (empty(self::$theme)) ? App::get('theme') : self::$theme;
        if (empty($path))
            $path = (empty(self::$pathTheme)) ? self::path(App::get('path-theme')) : self::$pathTheme;
        return self::path($path . '/' . $theme . '/' . $url);
    }

    /**
     * Get path
     *
     * @param string|null $path
     * @param string|null $app
     * @return string
     */
    public static function path($path = null, $app = null)
    {
        $result = Loader::basePath();

        $isBase = false;

        $path = self::ds($path);
        if ($app !== '~' && !HelperString::firstHas($path, '~')) {
            $packageName = is_null($app) ? App::package() : $app;
            $result .= '/'.Router::app_folder .'/'. $packageName .'/';
        } else {
            $isBase = true;
            $path = HelperString::firstDelete($path, '~');
        }

        if (!is_null($path)) {
            if (!$isBase) {
                $path = HelperString::firstDelete($path, self::app());
                $path = HelperString::firstDelete($path, Url::app());
            } else {
                $path = HelperString::firstDelete($path, Loader::basePath());
                $path = HelperString::firstDelete($path, Url::site());
            }
            $path = self::ds($path);
            $path = HelperString::firstDelete($path,'/');
            $result = $result .'/'. $path;

        }

        return $result;
    }

    /**
     * Get path app
     *
     * @return string
     */
    private static function app()
    {
        return self::ds(Loader::basePath() .'/'. Router::app_folder . '/' . App::package() . '/');
    }

    /**
     * Get path thumb images
     *
     * @param string|array|int|mixed $img
     * @param string|int $thumbSize
     * @param string|null $defaultImage
     * @param string $path
     * @param bool $isCreateThumb
     * @param bool $isCheck
     * @return mixed|string|null
     */
    public static function thumb($img, $thumbSize = 128, $defaultImage = null, $path = PINOOX_PATH_THUMB, $isCreateThumb = false, $isCheck = true)
    {
        if (empty($img)) return $defaultImage;

        if (is_array($img) || is_numeric($img))
            $img = self::upload($img, null,$isCheck);

        if (empty($img)) return $defaultImage;

        $path = dirname($img) .'/'. $path;
        $name = File::name($img);
        $ext = File::extension($img);
        $filename = $name . '.'.$ext;
        $fix = false;

        if (HelperString::lastHas($thumbSize, 'f')) {
            $fix = true;
            $thumbSize = HelperString::lastDelete($thumbSize, 'f');
        }


        $dirThumb = HelperString::replaceData($path,[
            'name' => $name,
            'size' => $thumbSize,
            'ext' => $ext,
            'filename' => $filename,
        ]);

        if (is_file($dirThumb)) {
            return $dirThumb;
        } else if ($isCreateThumb) {
            File::make_folder($path, true);
            if (ImageProcess::resize($img, $dirThumb, $thumbSize, $thumbSize, $fix)) {
                return $dirThumb;
            }
        } else if (!$isCheck) {
            return $dirThumb;
        }
        return $defaultImage;
    }

    /**
     * Get path files uploaded
     *
     * @param string|array|int|mixed $row
     * @param string|null $defaultPath
     * @param bool $isCheck
     * @return string|null
     */
    public static function upload($row, $defaultPath = null, $isCheck = true)
    {
        if (!is_array($row) && is_numeric($row))
            $row = FileModel::fetch_by_id($row);

        if (empty($row)) return $defaultPath;

        $file_path = (isset($row['dir_file'])) ? $row['dir_file'] : $row['file_path'];
        $file_name = (isset($row['uploadname'])) ? $row['uploadname'] : $row['file_name'];
        $path = self::path('~' . $file_path . $file_name);
        if ($isCheck && !is_file($path))
            return $defaultPath;

        return $path;
    }

    /**
     * Convert string path by a directory separator
     *
     * @param string $path
     * @return string|mixed
     */
    public static function ds($path)
    {
        return str_replace(['\\', '>'], '/', $path);
    }

}
    
