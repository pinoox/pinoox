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

use pinoox\component\app\AppProvider;
use pinoox\model\FileModel;

class Dir
{
    private static $theme = null;
    private static $pathTheme = null;

    public static function setTheme($theme, $path = null)
    {
        self::$theme = $theme;
        self::$pathTheme = $path;
    }

    public static function theme($url = null, $theme = null, $path = null)
    {
        if (empty($theme))
            $theme = (empty(self::$theme)) ? AppProvider::get('theme') : self::$theme;
        if (empty($path))
            $path = (empty(self::$pathTheme)) ? Dir::path(AppProvider::get('path-theme')) : self::$pathTheme;
        return self::path($path . '/' . $theme . '/' . $url);
    }

    public static function path($path = null, $app = null)
    {
        $result = PINOOX_PATH;

        $isBase = false;

        if ($app !== '~' && !HelperString::firstHas($path, '~')) {
            $packageName = is_null($app) ? Router::getApp() : $app;
            $result .= Router::app_folder . DIRECTORY_SEPARATOR . $packageName . DIRECTORY_SEPARATOR;
        } else {
            $isBase = true;
            $path = HelperString::firstDelete($path, '~');
        }

        if (!is_null($path)) {
            if (!$isBase) {
                $path = HelperString::firstDelete($path, self::app());
                $path = HelperString::firstDelete($path, Url::app());
            } else {
                $path = HelperString::firstDelete($path, PINOOX_PATH);
                $path = HelperString::firstDelete($path, Url::site());
            }
            $path = self::ds($path);
            $path = HelperString::firstDelete($path, DIRECTORY_SEPARATOR);
            $result = $result . $path;
        }
        return $result;
    }

    private static function app()
    {
        return PINOOX_PATH . Router::app_folder . DIRECTORY_SEPARATOR . Router::getApp() . DIRECTORY_SEPARATOR;
    }

    public static function thumb($img, $thumbSize = 128, $defaultImage = null, $path = PINOOX_PATH_THUMB, $isCreateThumb = false, $isCheck = true)
    {
        if (empty($img)) return $defaultImage;

        if (is_array($img) || is_numeric($img))
            $img = self::upload($img, null,$isCheck);

        if (empty($img)) return $defaultImage;

        $path = dirname($img) .DIRECTORY_SEPARATOR. $path;
        $name = File::name_file($img);
        $ext = File::ext_file($img);
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

    public static function ds($path)
    {
        return str_replace(['/', '\\', '>'], DIRECTORY_SEPARATOR, $path);
    }

}
    
