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

class File
{
    private static $whereDirectory;

    // rename (folder or file) : changeName(file,newName)
    public static function changeName($file, $newName)
    {
        if (!file_exists($file)) return false;
        $path = dirname($file) . DIRECTORY_SEPARATOR;

        $r = @rename($file, $path . $newName);

        return $r ? true : false;
    }

    // rename (folder or file)
    public static function renameDir($file, $rename)
    {
        if (!file_exists($file)) return false;
        $folder = dirname($file);
        $newFile = $folder . DIRECTORY_SEPARATOR . $rename;
        $r = @rename($file, $newFile);

        return $r ? true : false;
    }

    // move (folder or file)
    public static function moveDir($file, $newFile)
    {
        if (!file_exists($file)) return false;
        $folder = dirname($newFile);
        self::make_folder($folder, true);

        $m = @rename($file, $newFile);

        return $m ? true : false;
    }

    // copy (folder or file)

    public static function make_folder($folder, $multi = false, $chmod = 0777, $safe = true)
    {

        $data = 'no access ...';
        $pathIndex = $folder . DIRECTORY_SEPARATOR . 'index.html';
        $pathHtaccess = $folder . DIRECTORY_SEPARATOR . '.htaccess';

        if (is_dir($folder)) {
            if ($safe) {
                if (!is_file($pathIndex)) {
                    File::generate_file($pathIndex, $data);
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
            File::generate_file($pathIndex, $data);
            File::generate_htaccess($folder);
        }

        return $f ? true : false;
    }

    // make folder : make_folder("folder",false,0777);
    // delete all sub files and sub folders in one directory

    /**
     * create htaccess
     */
    public static function generate_htaccess($folder, $data = null)
    {
        #data for the htaccess
        if (empty($data)) {
            $data = "<Files ~ \"^.*\.(php|php*|cgi|pl|phtml|shtml|sql|asp|aspx)\">\nOrder allow,deny\nDeny from all\n</Files>\n<IfModule mod_php4.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php5.c>\nphp_flag engine off\n</IfModule>\nRemoveType .php .php* .phtml .pl .cgi .asp .aspx .sql";
        }


        #generate the htaccess
        return (self::generate_file($folder . "/.htaccess", $data)) ? true : false;
    }

    // delete file or folder and all sub files and sub folders

    public static function copyDir($file, $newFile)
    {
        if (!file_exists($file)) return false;
        $folder = dirname($newFile);
        self::make_folder($folder, true);

        $c = @copy($file, $newFile);

        return $c ? true : false;
    }

    // delete file

    public static function remove_all_into_dir($directory, $pattern = "*")
    {
        if (!file_exists($directory)) return;

        foreach (glob("{$directory}/" . $pattern) as $file) {
            if (is_dir($file)) {
                self::removedir($file);
            } else {
                unlink($file);
            }
        }
    }

    // get file size

    public static function removedir($directory, $pattern = "*")
    {
        if (is_array($directory)) {
            foreach ($directory as $file) {
                if (file_exists($file))
                    self::removedir($file);
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
                self::removedir($file);
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

    // get file time

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

    // check file is Image : true or false

    public static function file_size($file, $unitSize = 'b', $format = null)
    {
        if (is_file($file)) {
            $size = self::convert_size(filesize($file), 'B', $unitSize);
            if ($format != null)
                return HelperString::format($size, $format);
            return $size;
        }
        return 0;
    }

    // get type file

    public static function convert_size($input, $type_input, $type_Convert,$round = 0, $print = false)
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
            return round($output,$round);
        }
        if ($print) return "0 " . $type_Convert;
        return 0;
    }

    // get mime type file

    public static function file_time($file)
    {
        if (is_file($file)) {
            return @filemtime($file);
        }
        return null;
    }

    // get name file without ext

    public static function isImg($ext, $img_types = ["png", "jpg", "gif"])
    {
        if (empty($ext)) return false;

        if (in_array($ext, $img_types)) return true;
        return false;
    }

    // get name folder & file

    public static function name_file($file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    // get filename and ext

    public static function getNameBySlice($path, $slice = 0, $delimiter = DIRECTORY_SEPARATOR)
    {

        $arrPath = explode($delimiter, $path);
        $count = count($arrPath);
        $arrPath = array_slice($arrPath, 0, $count - $slice);
        $path = implode($delimiter, $arrPath);
        return basename($path);
    }

    // get dir file

    public static function name_and_ext_file($file)
    {
        return pathinfo($file, PATHINFO_BASENAME);
    }

    // type file in exts

    public static function dir_file($file)
    {
        return pathinfo($file, PATHINFO_DIRNAME);
    }

    // get file size form link

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
                if ($h == "\r\n" OR $h == "\n") break;
                list($key, $value) = explode(":", $h, 2);
                $headers[$key] = trim($value);
                if ($code >= 300 AND $code < 400 AND strtolower($key) == "location" AND $redirect > 0) {
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

    // get automatic size (TB,GB,MB,KB,B)
    public static function convert_print_size($bytes, $round = 2, $lang = [])
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

    // get convert size : convert_size(1,"MB","KB") return 1024

    public static function get_name_folders($dir)
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

    public static function get_dir_folders($directory, $directory_seperator = DIRECTORY_SEPARATOR)
    {
        if (!file_exists($directory)) return;

        $dirs = array_map(function ($item) use ($directory_seperator) {
            return $item . $directory_seperator;
        }, glob($directory . "*", GLOB_ONLYDIR));

        return $dirs;
    }

    // get all folder and sub folders

    public static function get_files_by_pattern($dir, $pattern = '*', $flag = 0)
    {
        if (!file_exists($dir)) return;

        $files = array();
        foreach (glob("{$dir}" . $pattern, $flag) as $file) {
            if (is_file($file)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    // get all folder and sub folders

    public static function get_pro_directory($dir, $directory_seperator = DIRECTORY_SEPARATOR)
    {
        $filter = self::$whereDirectory;
        self::$whereDirectory = null;
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

    // get all folder and sub folders
    // use filter no check folder : get_pro_folder("folder","/",array("no_folder"))

    private static function get_pro_folders($directory, $directory_seperator = DIRECTORY_SEPARATOR, $no_dirs = array())
    {
        if (!file_exists($directory)) return;

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

    // get all files in only folder
    // use filter no check file : array("file1","file2")
    // use filter no check type : array("php","jpg")
    // use filter only check type : array("php","jpg"),"in"

    private static function get_all_folders($directory, $directory_seperator = DIRECTORY_SEPARATOR)
    {
        if (!file_exists($directory)) return;

        $dirs = array_map(function ($item) use ($directory_seperator) {
            return $item . $directory_seperator;
        }, array_filter(glob($directory . "*"), 'is_dir'));
        foreach ($dirs AS $dir) {
            $dirs = array_merge($dirs, self::get_all_folders($dir, $directory_seperator));
        }

        return $dirs;
    }

    // get all files in folder
    // by pattern

    private static function get_pro_files($dirs, $no_file = array(), $exts = array(), $ext_action = "out")
    {
        $arr_file = array();
        foreach ($dirs as $dir) {
            $get_files = self::get_files($dir, $no_file, $exts, $ext_action);
            $arr_file = array_merge($arr_file, $get_files);
        }
        return $arr_file;
    }

    // get all files in folder and sub folders
    // use filter no check file : array("file1","file2")
    // use filter no check type : array("php","jpg")

    public static function get_files($directory, $no_file = array(), $exts = array(), $ext_action = "out")
    {
        if (!file_exists($directory)) return;
        $arr_files = array();
        $get_files = scandir($directory);
        $get_files = array_filter($get_files);
        $get_files = array_diff($get_files, array(".", ".."));

        foreach ($get_files as $get_file) {
            $file = $directory . $get_file;
            $ext = self::ext_file($file);
            $check_ext = true;
            if (!empty($exts)) {
                if ($ext_action == "out") {
                    if (in_array($ext, $exts)) $check_ext = false;
                } else if ($ext_action == "in") {
                    if (!self::in_exts($file, $exts)) $check_ext = false;
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

    // get all files and folders into folder and sub folders
    // use filter is only folder : $filter['is_folder'] = true;
    // use filter is only file : $filter['is_file'] = true;
    // use filter no check file : $filter['no_file'] = array("file1","file2");
    // use filter no check folder : $filter['no_folder'] = array("folder1","folder2");
    // use filter no check type : $filter['out_exts'] = array("php","jpg");
    // use filter only check in type : $filter['in_exts'] = array("php","jpg")
    // example: get_pro_directory("folder","/")

    public static function ext_file($file)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    public static function in_exts($file, $types)
    {
        $type = $file;
        if (is_file($file))
            $type = self::ext_file($file);
        if (in_array($type, $types)) return true;
        return false;
    }

    public static function where($key, $value)
    {
        self::$whereDirectory[$key] = $value;
    }

    // return auto type file like method get_file_type but auto

    /**
     * to detect file type put full path of a file or extension of a file
     *
     * example1)
     *  $file = /root/uploads/video.mp4
     * output = video
     *
     * * example2)
     *  $file = png
     * output = image
     */
    public static function get_file_type($file)
    {
        $fileExt = self::ext_file($file);
        $types = array(
            "image" => array('png', 'jpeg', 'jpg', 'gif', 'bmp', 'tiff'),
            "video" => array('mp4', 'avi', 'mkv', 'mov', 'wmv'),
            "audio" => array('mp3', 'wav', 'ogg', 'wma', 'aac', 'flac', 'm4a', 'ra', 'rm', 'mid'),
            "document" => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt'),
            "archive" => array('zip', 'rar', 'tar'),
        );
        foreach ($types as $type => $exts) {
            if (self::in_exts($fileExt, $exts) != false)
                return $type;
        }
        return 'file';
    }

    public static function get_auto_file_type($file_or_mime_type)
    {
        if (is_file($file_or_mime_type)) {
            $mime_type = self::mime_type_file($file_or_mime_type);
        } else {
            $mime_type = $file_or_mime_type;
        }

        $type = explode('/', $mime_type);

        return $type[0];
    }

    public static function mime_type_file($file)
    {
        if (!is_file($file)) return false;
        return mime_content_type($file);
    }

    /**
     * create file
     */
    public static function generate_file($path, $data)
    {
        $folder = dirname($path);
        if (!is_dir($folder))
            self::make_folder($folder, true, 0777, false);
        #generate the file
        $file = @fopen($path, "w");
        return (@fwrite($file, $data)) ? true : false;
    }

    public static function getArrayInto($file, $once = false)
    {
        if (is_file($file)) {
            if ($once)
                $arr = include_once $file;
            else
                $arr = include $file;
        }
        return (!empty($arr)) ? $arr : array();
    }

    public static function is_exists_file_by_url($url, $remove = null)
    {
        if (empty($remove)) $remove = Url::site();
        $url = str_replace($remove, '', $url);
        $path = Dir::path($url);
        return is_file($path);
    }
}