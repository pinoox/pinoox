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

use ZipArchive;

class Zip
{
    private static $entries = null;

    public static function folders($zippedFile, $isJustCurrent = false, $dir = null)
    {
        $files = self::info($zippedFile, $isJustCurrent, $dir);
        $result = [];
        foreach ($files as $index => $file) {
            if ($file['is_dir'])
                $result[$index] = $file;
        }
        return $result;
    }

    public static function info($zippedFile, $isJustCurrent = false, $dir = null)
    {
        if (!is_file($zippedFile)) return null;
        $zip = new ZipArchive();
        $zip->open($zippedFile);
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if ($isJustCurrent) {
                if (!empty($dir) && !HelperString::lastHas($dir, '/')) $dir .= '/';
                $string = HelperString::firstDelete($stat['name'], $dir);
                $string = HelperString::lastDelete($string, '/');
                if (!$string || HelperString::has($string, ['/', '\\']) || !HelperString::firstHas($stat['name'], $dir)) continue;
            }
            $isDir = (HelperString::lastHas($stat['name'], ['/', '\\']));
            $files[] = [
                'filename' => $stat['name'],
                'filesize' => $stat['size'],
                'comp_size' => $stat['comp_size'],
                'comp_method' => $stat['comp_method'],
                'datetime' => Date::g('Y-m-d H:i:s', $stat['mtime']),
                'is_dir' => $isDir];
        }
        return $files;
    }

    public static function files($zippedFile, $isJustCurrent = false, $dir = null)
    {
        $files = self::info($zippedFile, $isJustCurrent, $dir);
        $result = [];
        foreach ($files as $index => $file) {
            if (!$file['is_dir'])
                $result[$index] = $file;
        }
        return $result;
    }

    // create zip
    public static function archive($source, $zipname = null, $overwrite = false, $no_file = array(), $exts = array(), $ext_action = "out")
    {
        $zipname = is_null($zipname) || empty($zipname) ? HelperString::get_unique_string(is_array($source) ? $source[0] : $source, 'md5') . '.zip' : $zipname;
        if (is_array($source) || is_dir($source)) {
            if (!is_array($source))
                $files = File::get_files($source, $no_file, $exts, $ext_action);
            else {
                $files = $source;
            }
            if (empty($files))
                return false;
            $valid_files = array();
            foreach ($files as $f)
                if (file_exists($f))
                    $valid_files[] = $f;
            if (empty($valid_files))
                return false;
            $zip = new ZipArchive();
            $destination = pathinfo($source[0], PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $zipname;
            if (!file_exists($destination))
                $overwrite = false;
            if ($zip->open($destination, $overwrite ? ZipArchive::OVERWRITE : ZipArchive::CREATE) !== true)
                return false;
            foreach ($valid_files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            return file_exists($destination) ? $zipname : false;
        } else if (is_file($source)) {
            if (!file_exists($source)) return false;

            $zip = new ZipArchive();
            $destination = str_replace(basename($source), '', $source) . $zipname;
            if (!file_exists($destination))
                $overwrite = false;
            if ($zip->open($destination, $overwrite ? ZipArchive::OVERWRITE : ZipArchive::CREATE) !== true)
                return false;
            $zip->addFile($source, basename($source));
            $zip->close();
            return file_exists($destination) ? $zipname : false;
        } else {
            return false;
        }

    }


    public static function addEntries($filename)
    {
        if(!is_array(self::$entries))
            self::$entries = [];
        array_push(self::$entries,$filename);
    }

    public static function entries($filenames)
    {
        self::$entries = $filenames;
    }

    public static function extract($zippedFile, $dir)
    {
        $zip = new ZipArchive;
        $res = $zip->open($zippedFile);
        if ($res === TRUE) {
            $zip->extractTo($dir,self::$entries);
            self::$entries = null;
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    public static function remove($zippedFile, $path)
    {
        $zip = new ZipArchive;
        if ($zip->open($zippedFile) === TRUE) {
            if (!is_array($path))
                $path = [$path];

            foreach ($path as $p) {
                $zip->deleteName($p);
            }

            $zip->close();
            return true;
        } else {
            return false;
        }
    }
}