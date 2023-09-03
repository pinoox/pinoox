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

use pinoox\component\helpers\HelperString;

class File
{
    /**
     * Data filter for fetch directories & files
     *
     * @var array
     */
    private static $whereDirectory = [];

    /**
     * Rename folder or file
     *
     * @param string $file
     * @param string $newName
     * @return bool
     */
    public static function rename($file, $newName)
    {
        if (!file_exists($file)) return false;
        $path = dirname($file) . DIRECTORY_SEPARATOR;

        $r = @rename($file, $path . $newName);

        return $r ? true : false;
    }

    /**
     * Move folder or file
     *
     * @param string $file
     * @param string $newFile
     * @return bool
     */
    public static function move($file, $newFile)
    {
        if (!file_exists($file)) return false;
        $folder = dirname($newFile);
        self::make_folder($folder, true);

        $m = @rename($file, $newFile);

        return $m ? true : false;
    }

    /**
     * Make folder
     *
     * @param string $folder
     * @param bool $multi
     * @param int $chmod
     * @param bool $safe
     * @return bool
     */
    public static function make_folder($folder, $multi = false, $chmod = 0777, $safe = true)
    {

        $data = 'no access ...';
        $pathIndex = $folder . DIRECTORY_SEPARATOR . 'index.html';
        $pathHtaccess = $folder . DIRECTORY_SEPARATOR . '.htaccess';

        if (is_dir($folder)) {
            if ($safe) {
                if (!is_file($pathIndex)) {
                    File::generate($pathIndex, $data);
                }
                if (!is_file($pathHtaccess)) {
                    File::generate_htaccess($folder);
                }
            }
            return true;
        }

        #try to make a new upload folder
        $f = @mkdir($folder, $chmod, $multi);

        if ($safe) {
            File::generate($pathIndex, $data);
            File::generate_htaccess($folder);
        }

        return $f ? true : false;
    }

    /**
     * Generate file
     *
     * @param string $path
     * @param string $data
     * @return bool
     */
    public static function generate($path, $data)
    {
        $folder = dirname($path);
        if (!is_dir($folder))
            self::make_folder($folder, true, 0777, false);
        #generate the file
        $file = @fopen($path, "w");
        return (@fwrite($file, $data)) ? true : false;
    }

    /**
     * Create htaccess file
     *
     * @param string $folder
     * @param string $data
     * @return bool
     */
    public static function generate_htaccess($folder, $data = null)
    {
        #data for the htaccess
        if (empty($data)) {
            $data = "<Files ~ \"^.*\.(php|php*|cgi|pl|phtml|shtml|sql|asp|aspx)\">\nOrder allow,deny\nDeny from all\n</Files>\n<IfModule mod_php4.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php5.c>\nphp_flag engine off\n</IfModule>\nRemoveType .php .php* .phtml .pl .cgi .asp .aspx .sql";
        }


        #generate the htaccess
        return (self::generate($folder . "/.htaccess", $data)) ? true : false;
    }

    /**
     * Copy file or folder
     *
     * @param string $file
     * @param string $newFile
     * @param bool $safe
     * @return bool
     */
    public static function copy($file, $newFile, $safe = false)
    {
        if (!file_exists($file)) return false;
        $folder = dirname($newFile);
        self::make_folder($folder, true, 0777, $safe);

        $c = @copy($file, $newFile);

        return $c ? true : false;
    }

    /**
     * Delete all files in a folder
     *
     * @param string $directory
     * @param string $pattern
     */
    public static function delete_all_files_in_folder($directory, $pattern = "*")
    {
        if (!file_exists($directory)) return;

        foreach (glob("{$directory}/" . $pattern) as $file) {
            if (is_dir($file)) {
                self::remove($file);
            } else {
                unlink($file);
            }
        }
    }

    /**
     * Remove file or folder
     *
     * @param string $directory
     * @param string $pattern
     */
    public static function remove($directory, $pattern = "*")
    {
        if (is_array($directory)) {
            foreach ($directory as $file) {
                if (file_exists($file))
                    self::remove($file);
            }
            return;
        }

        if (is_file($directory)) {
            unlink($directory);
            return;
        }

        if (!file_exists($directory)) return;
        $paths = scandir($directory);
        $paths = array_filter($paths);
        $paths = array_diff($paths, array(".", ".."));
        foreach ($paths as $file) {
            $file = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                self::remove($file);
            } else {
                if (file_exists($file))
                    self::remove_file($file);
            }
        }
        $pathHtAccess = $directory . DIRECTORY_SEPARATOR . '.htaccess';
        if (file_exists($pathHtAccess)) {
            self::remove_file($pathHtAccess);
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }

    }

    /**
     * Remove files
     *
     * @param string|array $file
     */
    public static function remove_file($file)
    {
        if (is_array($file)) {
            foreach ($file as $f) {
                if (is_file($f))
                    unlink($f);
            }
        } else {
            if (is_file($file))
                unlink($file);
        }
    }

    /**
     * Get file size
     *
     * @param string $file
     * @param string $unitSize
     * @param int|null $format
     * @return float|int|string
     */
    public static function size($file, $unitSize = 'b', $format = null)
    {
        if (is_file($file)) {
            $size = self::convert_size(filesize($file), 'B', $unitSize);
            if ($format != null)
                return HelperString::format($size, $format);
            return $size;
        }
        return 0;
    }

    /**
     * Convert size unit
     *
     * @param string $input
     * @param string $type_input
     * @param string $type_Convert
     * @param int $round
     * @param bool $print
     * @return float|int|string
     */
    public static function convert_size($input, $type_input, $type_Convert, $round = 0, $print = false)
    {
        if ($input == 0) return ($print) ? $input . " " . $type_Convert : $input;
        $type_Convert = strtoupper($type_Convert);
        $type_input = strtoupper($type_input);
        $size_byte = array("TB" => pow(1024, 4), "GB" => pow(1024, 3), "GB" => pow(1024, 3), "MB" => pow(1024, 2), "KB" => 1024, "B" => 1);

        if (isset($size_byte[$type_input]) && isset($size_byte[$type_Convert])) {
            $size_input = $size_byte[$type_input];
            $size_convert = $size_byte[$type_Convert];
            $typeSize = $size_input / $size_convert;
            $output = $input * $typeSize;
            if ($print) $output .= " " . $type_Convert;
            return round($output, $round);
        }
        if ($print) return "0 " . $type_Convert;
        return 0;
    }

    /**
     * Get automatic size (TB,GB,MB,KB,B)
     *
     * @param $size
     * @param int $round
     * @return float|int|string|null
     */
    public static function print_size($size, $round = 2)
    {
        $result = null;
        $size = floatval($size);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

        foreach ($arBytes as $arItem) {
            if ($size >= $arItem["VALUE"]) {
                $result = $size / $arItem["VALUE"];
                $result = Lang::replace('~file.units.' . $arItem["UNIT"], str_replace(".", ",", strval(round($result, $round))));
                break;
            }
        }
        return $result;
    }

    /**
     * Get file time
     *
     * @param string $file
     * @return false|int|null
     */
    public static function file_time($file)
    {
        if (is_file($file)) {
            return @filemtime($file);
        }
        return null;
    }

    /**
     * Get filename without extension
     *
     * @param string $file
     * @return mixed
     */
    public static function name($file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Get part of filename
     *
     * @param string $path
     * @param int $slice
     * @param string $delimiter
     * @return string
     */
    public static function get_name_by_slice($path, $slice = 0, $delimiter = DIRECTORY_SEPARATOR)
    {

        $arrPath = explode($delimiter, $path);
        $count = count($arrPath);
        $arrPath = array_slice($arrPath, 0, $count - $slice);
        $path = implode($delimiter, $arrPath);
        return basename($path);
    }

    /**
     * Get filename with extension
     *
     * @param string $file
     * @return mixed
     */
    public static function fullname($file)
    {
        return pathinfo($file, PATHINFO_BASENAME);
    }

    /**
     * Get directory file
     *
     * @param $file
     * @return mixed
     */
    public static function dir($file)
    {
        return pathinfo($file, PATHINFO_DIRNAME);
    }

    /**
     * Get size of file URL
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param int $redirect
     * @return array|int|string
     */
    public static function get_remote_file_size($url, $method = "GET", $data = "", $redirect = 10)
    {
        $url = parse_url($url);
        $fp = @fsockopen($url['host'], (!empty($url['port']) ? (int)$url['port'] : 80), $errno, $errstr, 30);
        if ($fp) {
            $path = (!empty($url['path']) ? $url['path'] : "/") . (!empty($url['query']) ? "?" . $url['query'] : "");
            $header = "\r\nHost: " . $url['host'];
            if ("post" == strtolower($method)) {
                $header .= "\r\nContent-Length: " . strlen($data);
            }

            fputs($fp, $method . " " . $path . " HTTP/1.0" . $header . "\r\n\r\n" . ("post" == strtolower($method) ? $data : ""));
            if (!feof($fp)) {
                $scheme = fgets($fp);
                list(, $code) = explode(" ", $scheme);
                $headers = array("Scheme" => $scheme);
            }

            while (!feof($fp)) {
                $h = fgets($fp);
                if ($h == "\r\n" or $h == "\n") break;
                list($key, $value) = explode(":", $h, 2);
                $headers[$key] = trim($value);
                if ($code >= 300 and $code < 400 and strtolower($key) == "location" and $redirect > 0) {
                    return self::get_remote_file_size($headers[$key], $method, $data, --$redirect);
                }
            }

            //$body = "";
            //while ( !feof($fp) ) $body .= fgets($fp);
            fclose($fp);
        } else {
            return (array("error" => array("errno" => $errno, "errstr" => $errstr)));
        }

        return !isset($headers["Content-Length"]) ? 0 : (string)$headers["Content-Length"];
    }

    /**
     *  Print convert size auto unit
     *
     * @param $bytes
     * @param int $round
     * @param array $lang
     * @return mixed|string
     */
    public static function convert_auto_unit($bytes, $round = 2, $lang = [])
    {
        $size = 0;
        $unit = 'B';
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $size = $bytes / $arItem["VALUE"];
                $unit = $arItem["UNIT"];
                $size = strval(round($size, $round));
                break;
            }
        }

        if (isset($lang[$unit]))
            $result = HelperString::replaceData($lang[$unit], $size);
        else
            $result = $size . " " . $unit;


        return $result;
    }

    /**
     * Get folder names
     *
     * @param string $dir
     * @return array
     */
    public static function get_folder_names($dir)
    {
        $get_folders = scandir($dir);
        $get_folders = array_filter($get_folders);
        $get_folders = array_diff($get_folders, array(".", ".."));

        $result = [];
        foreach ($get_folders as $folder) {
            if (is_dir($dir . $folder)) {
                $result[] = $folder;
            }
        }
        return $result;
    }

    // get all folder and sub folders

    /**
     * Get folders path
     *
     * @param string $directory
     * @param string $directory_seperator
     * @return array
     */
    public static function get_dir_folders($directory, $directory_seperator = DIRECTORY_SEPARATOR)
    {
        if (!file_exists($directory)) return [];

        $dirs = array_map(function ($item) use ($directory_seperator) {
            return $item . $directory_seperator;
        }, glob($directory . "*", GLOB_ONLYDIR));

        return $dirs;
    }

    /**
     * Get folders path by pattern
     *
     * @param string $dir
     * @param string $pattern
     * @param int $flag
     * @return array
     */
    public static function get_files_by_pattern($dir, $pattern = '*', $flag = 0)
    {
        if (!file_exists($dir)) return [];

        $files = array();
        foreach (glob("{$dir}" . $pattern, $flag) as $file) {
            if (is_file($file)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * Get all files & folders with filter
     *
     * @param string $dir
     * @param string $directory_seperator
     * @return array
     */
    public static function get_pro_directory($dir, $directory_seperator = DIRECTORY_SEPARATOR)
    {
        $filter = self::$whereDirectory;
        self::$whereDirectory = [];
        $array_directory = array();
        $no_folders = array();
        $no_files = array();
        $exts = array();
        $ext_action = "out";
        $is_folder = false;
        $is_file = false;
        if (isset($filter['is_folder']) && $filter['is_folder'] == true) $is_folder = true;
        if (isset($filter['is_file']) && $filter['is_file'] == true) $is_file = true;
        if (!$is_folder && !$is_file) {
            $is_folder = true;
            $is_file = true;
        }

        if (isset($filter['no_folder'])) $no_folders = $filter['no_folder'];
        if (isset($filter['no_file'])) $no_files = $filter['no_file'];
        if (isset($filter['out_exts'])) {
            $exts = $filter['out_exts'];
        }
        if (isset($filter['in_exts'])) {
            $exts = $filter['in_exts'];
            $ext_action = "in";
        }
        $dirs = self::get_pro_folders($dir, $directory_seperator, $no_folders);
        if ($is_folder) $array_directory = array_merge($array_directory, $dirs);
        if ($is_file) $array_directory = array_merge($array_directory, self::get_pro_files($dirs, $no_files, $exts, $ext_action));
        return $array_directory;
    }

    /**
     * Get all folders with filter
     *
     * @param string $directory
     * @param string $directory_seperator
     * @param array $no_dirs
     * @return array
     */
    private static function get_pro_folders($directory, $directory_seperator = DIRECTORY_SEPARATOR, $no_dirs = array())
    {
        if (!file_exists($directory)) return [];

        $dirs = self::get_all_folders($directory, $directory_seperator);

        if (!empty($no_dirs)) {
            $no_directors = array();
            foreach ($no_dirs as $no_dir) {
                $no_directors = array_merge($no_directors, self::get_all_folders($no_dir, $directory_seperator));
            }
            $dirs = array_diff($dirs, $no_directors);
        }
        return $dirs;
    }

    /**
     * Get all folders
     *
     * @param string $directory
     * @param string $directory_seperator
     * @return array
     */
    private static function get_all_folders($directory, $directory_seperator = DIRECTORY_SEPARATOR)
    {
        if (!file_exists($directory)) return [];

        $dirs = array_map(function ($item) use ($directory_seperator) {
            return $item . $directory_seperator;
        }, array_filter(glob($directory . "*"), 'is_dir'));
        foreach ($dirs as $dir) {
            $dirs = array_merge($dirs, self::get_all_folders($dir, $directory_seperator));
        }

        return $dirs;
    }

    /**
     * Get all files with filter
     *
     * @param array $dirs
     * @param array $no_file
     * @param array $exts
     * @param string $ext_action
     * @return array
     */
    private static function get_pro_files($dirs, $no_file = array(), $exts = array(), $ext_action = "out")
    {
        $arr_file = array();
        foreach ($dirs as $dir) {
            $get_files = self::get_files($dir, $no_file, $exts, $ext_action);
            $arr_file = array_merge($arr_file, $get_files);
        }
        return $arr_file;
    }

    /**
     * Get all files
     *
     * @param string $directory
     * @param array $no_file
     * @param array $exts
     * @param string $ext_action
     * @return array
     */
    public static function get_files($directory, $no_file = array(), $exts = array(), $ext_action = "out")
    {
        if (!file_exists($directory)) return [];
        $arr_files = array();
        $get_files = scandir($directory);
        $get_files = array_filter($get_files);
        $get_files = array_diff($get_files, array(".", ".."));

        foreach ($get_files as $get_file) {
            $file = $directory . $get_file;
            $ext = self::extension($file);
            $check_ext = true;
            if (!empty($exts)) {
                if ($ext_action == "out") {
                    if (in_array($ext, $exts)) $check_ext = false;
                } else if ($ext_action == "in") {
                    if (!self::in_extension($file, $exts)) $check_ext = false;
                }
            }
            if (empty($no_file) || !in_array($file, $no_file)) {
                if ($check_ext) {
                    if (is_file($file)) {
                        $arr_files[] = $file;
                    }
                }
            }

        }
        return $arr_files;
    }

    /**
     * Get file extension
     *
     * @param string $file
     * @return string
     */
    public static function extension($file)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    /**
     * Check extension file in array extensions
     *
     * @param string $file
     * @param array $types
     * @return bool
     */
    public static function in_extension($file, $types)
    {
        $type = $file;
        if (is_file($file))
            $type = self::extension($file);
        if (in_array($type, $types)) return true;
        return false;
    }

    /**
     * Add filter for get all files & folders (method get_pro_directory)
     *
     * @param string $key
     * @param mixed $value
     */
    public static function where($key, $value)
    {
        self::$whereDirectory[$key] = $value;
    }


    /**
     * Get type file
     *
     * @param string $file_or_mime_type
     * @return mixed
     */
    public static function type($file_or_mime_type)
    {
        if (is_file($file_or_mime_type)) {
            $mime_type = self::mime_type($file_or_mime_type);
        } else {
            $mime_type = $file_or_mime_type;
        }

        $type = explode('/', $mime_type);

        return $type[0];
    }

    /**
     * Get mime-type by file
     *
     * @param string $file
     * @return bool|string
     */
    public static function mime_type($file)
    {
        if (!is_file($file)) return false;
        return mime_content_type($file);
    }

    public static function extract_namespace($file)
    {
        $ns = NULL;
        $handle = fopen($file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, 'namespace') === 0) {
                    $parts = explode(' ', $line);
                    $ns = rtrim(trim($parts[1]), ';');
                    break;
                }
            }
            fclose($handle);
        }
        return $ns;
    }

    public static function getBetweenLine($path, $start, $end): string
    {
        $result = '';
        if (!is_file($path))
            return $result;

        $lines = file($path);
        for ($i = $start; $i <= $end; $i++) {
            $result .= $lines[$i];
        }

        return $result;
    }
}