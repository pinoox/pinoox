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

class Upload
{
    const form_type = 'form';
    const copy_type = 'copy';
    const move_type = 'move';
    const base64_type = 'base64';

    protected static $object = null;
    private static $isStopUpload = false;
    private static $errStopUpload = false;
    public $dirSeparator = DIRECTORY_SEPARATOR;
    public $isAllType = false;
    public $result = null;
    private $fileInit = null;
    protected $isSave = false;
    protected $isTransaction = false;
    protected $thumbImg = array();
    private $allowedTypes = array();
    private $notAllowedTypes = array();
    private $isAllowedTypes = false;
    private $isName = false;
    private $typeFile = 'form';
    private $defaultAllSize = "*";
    private $typeSize = "MB";
    private $dirFolder;
    private $numLimit = 0;
    private $convert = "";
    private $postfix = "";
    private $prefix = "";
    private $getFile = array();
    private $resizeImg = array();
    private $watermark = array();
    private $infoConverter = array();
    private $tempFiles = array();
    private $err = array();
    private $beforeFunc;
    private $afterFunc;

    /*
     *
     * Example : show all method
     * ^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->dirSeparator = DIRECTORY_SEPARATOR;
     * $upload->isAllType = true;
     * $upload->folder("uploads");
     * $upload->sizeUnit("MB");
     * $upload->changeName("time",false,"_yo","te_");
     * $upload->resize(dir,500,400,true);
     * $upload->thumb(500,false,false,false);
     * $upload->converterImage('png',false,$new_dir);
     * $upload->limit(2);
     * $upload->watermark("logo.png","top","center");
     * $upload->allowedTypes("png=3,gif,txt",2);
     * $upload->notAllowedTypes("zip");
     * $upload->defaultSize(1024);
     * $upload->process();
     * $upload->error();
     * ########################################
     *
     * Example 1)
     * all allow type upload and down 100 MB
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->defaultSize(100);
     * $upload->process();
     *
     * ########################################
     *
     * Example 2)
     * only allow upload type png,gif,jpg
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->allowedTypes("png,gif,jpg");
     * $upload->process();
     *
     * ########################################
     *
     * Example 3)
     * all allow type upload
     * but zip and rar allow size down 1 GB
     * and mp4 down 2GB
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->isAllType = true;
     * $upload->sizeUnit("GB"); // default 'MB'
     * $upload->allowedTypes("mp4=2,zip,rar",1); // or array
     * $upload->process();
     * ########################################
     *
     * Example 4)
     * no allow type php and txt
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->notAllowedTypes("php,txt");
     * $upload->process();
     *
     * ########################################
     *
     *
     * Example 5)
     * no allow select more than 2 file
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->limit(2);
     * $upload->process();
     *
     * ########################################
     *
     * Example 6)
     * if fetal error server windows
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->dirSeparator = "\\";
     * $upload->process();
     *
     * ########################################
     *
     * Example 7)
     * uniq name upload (change name)
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->changeName("time"); // "time" or "md5" or "exists" or "uniqid" or empty
     * $upload->process();
     *
     * ########################################
     *
     * Example 8)
     * add Postfix and Prefix to name upload
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->setFolder("uploads");
     * $upload->setChangeName(null,false,'postfix','prefix');
     * $upload->process();
     *
     * ########################################
     *
     * Example 9)
     * resize image
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->resize("uploads/resize",100,100); // Width , Height
     * $upload->process();
     *
     *
     * =======> help resize +++++++++
     * (100,100)      => resize Width = 100 and Height = 100
     * ('auto',100)   => resize image with Height and Width = auto(byHeight)
     * (100,'auto')   => resize image with Width and Height = auto(byWidth)
     * (100,100,true) => resize if true get auto(width)
     *                   or auto(height) by each Whichever
     *                   is larger
     * +++++++++ help resize <========
     *
     * ########################################
     *
     * Example 10)
     * thumb image
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->thumb([128,256,512]);
     * $upload->process();
     *
     *
     * =======> help thumb +++++++++
     * parameter 1 : [128,256]   (array or string or int)  => 2 thumb
     * example:>>> 128 : thumbs/name.png | 256 : thumbs/name_256.png
     * parameter 2 : true   (bool) => thumb if true get auto(width)
     *                               or auto(height) by each Whichever
     *                               is larger
     * parameter 3 : true  (bool) => for each size create a folder
     *  example:>>> 128 : thumbs/128/name.png | 256 : thumbs/256/name.png
     * parameter 4 : true  (bool) => for each size create a postfix
     * example:>>> 128 : thumbs/name_128.png | 256 : thumbs/name_256.png
     * +++++++++ help thumb <========
     *
     * ########################################
     *
     * Example 11)
     * Converter image
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->converterImage('png');
     * $upload->process();
     *
     * =======> help Converter +++++++++
     * Second parameter if true  : delete old image (original image)
     * Third parameter get new dir for save
     * ########################################
     *
     *
     * Example 12)
     * watermark image
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->watermark("logo.png","top","center"); //default center
     * $upload->process();
     *
     * =======> help watermatk +++++++++
     * full help in imageProcess.class.php
     * +++++++++ help watermatk <========
     *
     *
     * ########################################
     *
     * Example 13)
     * get all error
     * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * $upload = new Upload('name');
     * $upload->folder("uploads");
     * $upload->process();
     * $upload->error(); // var_dump($upload->getError());
     *
     *
     * ########################################
     *
     */

    // set $_FILES['file'] by new class

    public function __construct($file = null, $folder = null)
    {
        if (!empty($file)) {
            $this->file($file);
        }

        if (!empty($folder)) {
            $this->folder($folder);
        }

    }

    public function file($file)
    {
        $this->reset();
        $this->fileInit = $file;

        return self::$object;
    }

    private function reset()
    {
        $this->isAllType = false;
        $this->allowedTypes = array();
        $this->notAllowedTypes = array();
        $this->isName = false;
        $this->defaultAllSize = "*";
        $this->typeSize = "MB";
        $this->typeFile = "form";
        $this->numLimit = 0;
        $this->convert = "";
        $this->postfix = "";
        $this->prefix = "";
        $this->getFile = array();
        $this->thumbImg = array();
        $this->resizeImg = array();
        $this->watermark = array();
        $this->infoConverter = array();
        $this->err = array();
        $this->result = null;
    }

    private function reArrayFiles($file_post)
    {
        if (!$this->isArrayFile($file_post)) return $file_post;
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }

    private function isArrayFile($file_post)
    {
        if (!isset($file_post['name']) || !is_array($file_post['name'])) return false;
        return true;
    }

    // set File by new class

    private function createListFile($files)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    $this->getFile[] = $this->getInfoFile($file);
                }
            }
        } else {
            if (is_file($files)) {
                $this->getFile = $this->getInfoFile($files);
            }
        }
    }

    // set File by new class

    private function createListForBase64($files)
    {
        $is_array = isset($files[0]) && is_array($files[0]);
        if ($is_array) {
            foreach ($files as $file) {
                $file = $this->getInfoFileBase64($file);
                if ($file) {
                    $this->getFile[] = $file;
                }
            }
        } else {
            $file = $this->getInfoFileBase64($files);
            if ($file) {
                $this->getFile = $file;
            }
        }
    }

    // set dir upload

    private function getInfoFile($file)
    {
        $result['tmp_name'] = $file;
        $result['name'] = File::fullname($file);
        $result['size'] = File::size($file);
        $result['type'] = File::mime_type($file);
        $result['error'] = 0;
        return $result;
    }

    private function getInfoFileBase64($file)
    {
        $base64Data = is_array($file) ? @$file['data'] : $file;

        if (empty($base64Data))
            return false;

        if (!preg_match('/^data:(?\'type\'.*);base64,(?\'file\'.*)/m', $base64Data, $data))
            return false;

        $type = $data['type'];
        $sliceType = explode('/', $type);

        if (empty($sliceType[1]))
            return false;

        $ext = strtolower($sliceType[1]);
        $dataFile = base64_decode($data['file']);
        $size = is_array($file) && !empty($file['size']) ? $file['size'] : strlen($dataFile);
        $name = is_array($file) && !empty($file['name']) ? $file['name'] : 'file.' . $ext;

        $result['tmp_name'] = $dataFile;
        $result['name'] = $name;
        $result['size'] = $size;
        $result['type'] = $type;
        $result['error'] = 0;
        return $result;
    }

    // set limit number upload

    private function setError($key, $err, $is_arr = false, $filename = null)
    {
        if ($is_arr) {
            $this->err['nested'][$key][] = $err;
            $this->err['no_nested'][] = $err;
            if ($filename != null)
                $this->err['by_filename'][$filename] = $err;
        } else {
            $this->err[$key] = $err;
        }
    }

    // set isTransaction

    public function folder($folder)
    {

        $lastChar = substr($folder, -1);
        if ($lastChar == $this->dirSeparator)
            $folder = substr($folder, 0, strlen($folder) - 1);

        if (!File::make_folder($folder, true)) $this->setError('MKDIR', Lang::replace('~upload.err.mkdir', $folder));
        else $this->dirFolder = $folder;

        return self::$object;

    }

    // set resize image

    public static function init($file = null, $folder = null)
    {
        self::$object = new Upload($file, $folder);
        return self::$object;
    }

    // set thumb image
    /*
     *
     * Example 1 :
     * thumbs/name.png
     * ^^^^^^^^^^^^^^^
     * $up->thumb(128);
     *
     * ####################
     *
     * Example 2 :
     * thumb/128/name.png
     * thumb/256/name.png
     * thumb/512/name.png
     * ^^^^^^^^^^^^^^^
     * $up->thumb([128,256,512],false,true);
     *
     * ####################
     *
     * Example 3 :
     * thumbs/name_128.png
     * thumbs/name_256.png
     * thumbs/name_512.png
     * ^^^^^^^^^^^^^^^
     * $up->thumb([128,256,512],false,false,true);
     *
     * ####################
     *
     *  Example 4 :
     * thumbs/name.png
     * thumbs/name_256.png
     * thumbs/name_512.png
     * ^^^^^^^^^^^^^^^
     * $up->thumb([128,256,512]);
     *
     * ####################
     *
     *  Example 5 :
     * thumbs/128/name_128.png
     * thumbs/256/name_256.png
     * thumbs/512/name_512.png
     * ^^^^^^^^^^^^^^^
     * $up->thumb([128,256,512],false,true,true);
     *
     */

    public static function getInstance($file = null, $folder = null)
    {
        if (empty(self::$object)) {
            self::$object = new Upload($file, $folder);
        } else {
            self::$object->file($file);
            self::$object->folder($folder);
        }
        return self::$object;
    }

    // set watermark on image

    public static function stopUpload($error = null, $is_arr = false, $filename = null)
    {
        self::$isStopUpload = true;
        if (!empty($error)) {
            self::$errStopUpload = [$error, $is_arr, $filename];
        }
    }

    // set Converter on image

    public function create($file, $folder)
    {
        self::$object->file($file);
        self::$object->folder($folder);

        return self::$object;
    }

    public function type($type = 'form')
    {
        $this->typeFile = $type;

        return self::$object;
    }

    public function copy()
    {
        $this->type('copy');

        return self::$object;
    }

    public function move()
    {
        $this->type(self::move_type);

        return self::$object;
    }

    public function base64()
    {
        $this->type('base64');

        return self::$object;
    }

    // set size unit : 'B' or 'KB' or 'MB' or 'GB' or 'TB'

    public function limit($limit)
    {
        if (is_int($limit)) {
            $this->numLimit = $limit;
        }
        return self::$object;
    }

    /*
     * set allow types and allow size : setAllowedTypes("png=2,gif=3,txt,pdf",3)
     * $exts = types and size  : png=2 or png
     * $sizeDefault : if = 3 result $exts="gif=2,png" => gif=2 and png=3
     */

    public function transaction($isTransaction = true)
    {
        $this->isTransaction = $isTransaction;
        return self::$object;
    }

    // set list Unauthorized extensions

    public function resize($dir, $w = 100, $h = 100, $fix = false)
    {

        $arr["h"] = $h;
        $arr["w"] = $w;
        $arr["fix"] = $fix;

        if (!File::make_folder($dir, true)) $this->setError('MKDIR', Lang::replace('~upload.err.mkdir', $dir));
        else $arr["path"] = $dir;

        $this->resizeImg["enable"] = true;
        $this->resizeImg['act'][] = $arr;
        return self::$object;
    }

    /*
     * set convet name upload :
     * $convert = "time" or "md5" or "exists" or "uniqid" or empty
     * $isName = true => name file upload = convert
     * $ext = postfix   name+$ext
     * $pre = prefix    name+$pre
     */

    /**
     * params:
     * @param $size → in array or string
     * @param $fixScale → bool => if true fix image by each Whichever is larger
     * @param $createFolder → bool => create folder with name size
     * @param $appendDimension → bool => add size postfix name image thumb
     *
     * return:
     * @return upload object
     */
    public function thumb($size = 100, $path = PINOOX_PATH_THUMB)
    {
        $this->thumbImg["enable"] = true;
        $this->thumbImg['size'] = $size;
        $this->thumbImg['path'] = $path;
        return self::$object;
    }

    // set errors in array $err : key,textError,multiUpload=false or true

    public function watermark($logo, $h = 1, $w = 1, $div = 2)
    {
        $arr["logo"] = $logo;
        $arr["h"] = $h;
        $arr["w"] = $w;
        $arr["div"] = $div;

        $this->watermark["enable"] = true;
        $this->watermark['act'][] = $arr;
        return self::$object;
    }

    // check file is has image? true or false

    public function converterImage($typeConvert = 'png', $is_old_delete = false, $new_dir = null)
    {
        $this->infoConverter["enable"] = true;
        $this->infoConverter["dir"] = $new_dir;
        $this->infoConverter["type"] = $typeConvert;
        $this->infoConverter["old_delete"] = $is_old_delete;
        return self::$object;
    }

    // get list errors

    public function defaultSize($size)
    {
        if (is_numeric($size)) {
            $this->defaultAllSize = $size;
        }
        return self::$object;
    }

    // stop upload

    public function sizeUnit($typeSize)
    {
        $typeSize = strtoupper($typeSize);
        if ($typeSize == "B" || $typeSize == "KB" || $typeSize == "MB" || $typeSize == "GB" || $typeSize == "TB") {
            $this->typeSize = $typeSize;
        } else {
            $this->typeSize = "MB";
        }
        return self::$object;
    }

    public function allowedTypes($exts, $sizeDefault = 0)
    {
        $this->isAllowedTypes = true;
        $this->allowedTypes = $this->renderExtSize($exts, $sizeDefault);
        return self::$object;
    }

    //process auto upload single or multi result return uploaded files

    private function renderExtSize($exts, $sizeDefault = "*")
    {
        $result = array();
        if (is_array($exts)) {
            foreach ($exts as $key => $value) {
                if (!is_numeric($key)) {
                    $result[$key] = (float)$value;
                } else {
                    if ($sizeDefault != "*") $result[$value] = (float)$sizeDefault;
                    else $result[$value] = ($this->defaultAllSize != '*') ? $this->defaultAllSize : '*';
                }
            }
        } else {
            $arrExts = explode(",", $exts);
            foreach ($arrExts as $ext) {
                if (strstr($ext, '=')) {
                    $get_ext = substr($ext, 0, strpos($ext, '='));
                    $get_size = (float)str_replace($get_ext . "=", '', $ext);
                    $result[$get_ext] = $get_size;
                } else {
                    if ($sizeDefault != "*") $result[$ext] = (float)$sizeDefault;
                    else $result[$ext] = ($this->defaultAllSize != '*') ? $this->defaultAllSize : '*';
                }
            }
        }
        return $result;
    }

    public function notAllowedTypes($exts)
    {
        if (is_array($exts))
            $this->notAllowedTypes = $exts;
        else
            $this->notAllowedTypes = explode(",", $exts);

        return self::$object;
    }

    public function changeName($convert, $isName = false, $ext = "", $pre = "")
    {
        $this->isName = $isName;
        $this->convert = $convert;
        $this->postfix = $ext;
        $this->prefix = $pre;

        return self::$object;
    }

    public function error($nested = false)
    {
        if ($nested === 'first') {
            return !empty($this->err['no_nested']) ? $this->err['no_nested'][0] : null;
        } else if ($nested === 'filename') {
            return isset($this->err['by_filename']) ? $this->err['by_filename'] : null;
        } else if (!$nested) {
            return isset($this->err['nested']) ? $this->err['nested'] : null;
        } else {
            return isset($this->err['no_nested']) ? $this->err['no_nested'] : null;
        }
    }

    public function result()
    {
        $result = $this->result;
        if (func_num_args() > 0) {
            $args = func_get_args();
            foreach ($args as $arg) {
                $result = $result[$arg];
            }
        }
        return $result;
    }

    // multi upload

    public function finish($single = false)
    {
        return $this->process($single, true);
    }

    // single upload

    public function process($single = false, $isObj = false)
    {
        $this->buildFile();

        if (!empty($this->err)) {
            if ($isObj)
                return self::$object;
            else
                return false;
        }

        $result = array();
        if ($single) {
            if (isset($this->getFile[0]['name'])) {
                $this->getFile = $this->getFile[0];
            }
            $result = $this->singleUpload();
        } else if (!empty($this->getFile)) {
            if (!isset($this->getFile['name']) && is_array($this->getFile)) {
                $result = $this->multiUpload();
            } else {
                $result[0] = $this->singleUpload();
            }
        }

        $this->result = $result;

        if ($isObj)
            return self::$object;
        else
            return $result;
    }

    private function buildFile()
    {
        $file = $this->fileInit;

        if ($this->typeFile === self::form_type) {
            if (!is_array($file) && isset($_FILES[$file])) {
                $this->getFile = $this->reArrayFiles($_FILES[$file]);
            }
        } else if ($this->typeFile === self::copy_type || $this->typeFile === self::move_type) {
            $this->createListFile($file);

        } else if ($this->typeFile === self::base64_type) {
            $this->createListForBase64($file);
        }

        if (empty($this->getFile)) {
            $this->setError('FILE_EMPTY', Lang::get('~upload.err.file_empty'));
        }
    }

    // for extends

    private function singleUpload($file = null)
    {
        if (empty($file)) $file = $this->getFile;

        self::$isStopUpload = false;
        self::$errStopUpload = null;
        $isUpload = true;
        $f_link = $file['tmp_name'];
        $f_filename = $file['name'];
        $f_size = $file['size'];
        $f_mimeType = $file['type'];
        $f_name = File::name($f_filename);
        $f_type = File::extension($f_filename);
        $u_filename = $this->getFileNameForUpload($f_name, $f_type);

        $return = [
            "uploadname" => $u_filename,
            "realname" => $f_filename,
            "size" => $f_size,
            "formattedSize" => File::convert_auto_unit($f_size, 2),
            "ext" => $f_type,
            'mimeType' => $f_mimeType,
            'type' => File::mime_type($f_mimeType),
            'dir_file' => ($this->dirFolder . $this->dirSeparator),
            'path_file' => ($this->dirFolder . $this->dirSeparator . $u_filename)
        ];
        if (!$this->validType($f_type)) {
            $isUpload = false;
            $this->setError("ALLOW_TYPE", Lang::replace('~upload.err.allow_type', $f_type), true, $f_filename);
        } else if (!$this->validSize($f_type, $f_size)) {
            $isUpload = false;
            $printSize = File::convert_auto_unit($this->getAllowSize($f_type));
            $this->setError("ALLOW_SIZE", Lang::replace('~upload.err.size', $f_type, $printSize), true, $f_filename);
        }
        if ($isUpload) {
            if ($this->isTransaction) {
                $this->tempFiles[] = ['tmp' => $f_link, 'converter' => $this->infoConverter, 'watermark' => $this->watermark, 'resize' => $this->resizeImg, 'thumb' => $this->thumbImg, 'folder' => $this->dirFolder, 'file' => $u_filename, 'type' => $f_type];
            }

            if (!empty($this->beforeFunc)) call_user_func($this->beforeFunc, $return);

            if (self::$isStopUpload) {
                if (!empty(self::$errStopUpload)) {
                    $this->setError('UPLOAD_STOP', self::$errStopUpload[0], self::$errStopUpload[1], self::$errStopUpload[2]);
                }
                return false;
            }

            if (!$this->isTransaction && !$this->actUpload($f_link, $u_filename)) {
                $this->setError("UPLOAD_FILE", Lang::replace('~upload.err.file', $f_filename), true, $f_filename);
                return false;
            }
            if (!$this->isTransaction && $this->isImg($f_type)) {

                if (isset($this->watermark["enable"]) && $this->watermark["enable"]) {
                    if (!$this->actImageWatermark($u_filename)) {
                        $this->setError("UPLOAD_WATERMARK_IMG", Lang::replace('~upload.err.watermark_img', $f_filename), true, $f_filename);
                    } else {
                        $return['watermark'] = true;
                    }
                }

                if (isset($this->thumbImg["enable"]) && $this->thumbImg["enable"]) {
                    if (!$result = $this->actImageThumb($u_filename)) {
                        $this->setError("UPLOAD_THUMB_IMG", Lang::replace('~upload.err.thumb_img', $f_filename), true, $f_filename);
                    } else {
                        $return['thumb'] = $result;
                    }
                }

                if (isset($this->resizeImg["enable"]) && $this->resizeImg["enable"]) {
                    if (!$result = $this->actImageResize($u_filename)) {
                        $this->setError("UPLOAD_RESIZE_IMG", Lang::replace('~upload.err.resize_img', $f_filename), true, $f_filename);
                    } else {
                        $return['resize'] = $result;
                    }
                }

                if (isset($this->infoConverter["enable"]) && $this->infoConverter["enable"]) {
                    if (!$result = $this->actImageConverter($u_filename)) {
                        $this->setError("UPLOAD_CONVERT_IMG", Lang::replace('~upload.err.convert_img', $f_filename), true, $f_filename);
                    } else {
                        $return['converter'] = $result;
                    }
                }
            }

            if (!empty($this->afterFunc)) call_user_func($this->afterFunc, [$return]);

            if ($this->isSave) $this->save($return);
            return $return;
        }


        return false;
    }

    // reset all option

    private function getFileNameForUpload($name, $type)
    {
        $dir = $this->dirFolder . $this->dirSeparator;
        if ($this->isName) {
            return $this->prefix . $this->convert . $this->postfix . "." . $type;
        }
        $filename = $name;
        $convert = strtolower($this->convert);
        $filename = HelperString::get_unique_string($filename, $convert, $this->prefix, $this->postfix, null, $dir, $type);
        $filename .= "." . $type;
        return $filename;
    }

    // act upload get input tmp_name , dir save file return true or false

    private function validType($type)
    {
        $return = false;
        $in_types = (!empty($this->allowedTypes)) ? array_keys($this->allowedTypes) : array();
        $out_types = (!empty($this->notAllowedTypes)) ? $this->notAllowedTypes : array();
        if (!empty($out_types)) {
            if (!in_array($type, $out_types)) {
                $return = true;
            }
        } else if ($this->isAllType) {
            $return = true;
        } else if (!empty($in_types)) {
            if (in_array($type, $in_types)) {
                $return = true;
            }
        } else if (!$this->isAllowedTypes) {
            $return = true;

        }

        return $return;
    }

    // act image resize

    private function validSize($type, $size)
    {
        $return = true;
        if (!empty($this->allowedTypes)) {
            if (isset($this->allowedTypes[$type])) {
                if ($this->allowedTypes[$type] != "*") {
                    $getSize = $this->getSize($this->allowedTypes[$type]);
                    if ($size > $getSize) $return = false;
                }
            }
        }

        if ($return && $this->defaultAllSize != "*") {
            $getSize = $this->getSize($this->defaultAllSize);
            if ($size > $getSize) $return = false;
        }

        return $return;
    }

    // act image thumb

    private function getSize($size)
    {
        return File::convert_size($size, $this->typeSize, "B");
    }

    // act print watermark on image

    private function getAllowSize($type)
    {
        return $this->getSize(isset($this->allowedTypes[$type]) ? $this->allowedTypes[$type] : $this->defaultAllSize);
    }

    // act Convert image

    private function actUpload($tmp, $file)
    {
        $return = false;

        if ($this->typeFile === self::copy_type) {
            if (File::copy($tmp, $this->dirFolder . $this->dirSeparator . $file, true)) {
                $return = true;
            }
        } else if ($this->typeFile === self::move_type) {
            if (File::move($tmp, $this->dirFolder . $this->dirSeparator . $file)) {
                $return = true;
            }
        } else if ($this->typeFile === self::base64_type) {
            if (File::generate($this->dirFolder . $this->dirSeparator . $file,$tmp)) {
                $return = true;
            }
        } else if ($this->typeFile === self::form_type) {
            if (move_uploaded_file($tmp, $this->dirFolder . $this->dirSeparator . $file)) {
                $return = true;
            }
        }

        return $return;
    }

    // get file name if convert active

    protected function isImg($type, $img_types = ["webp","png", "jpg", "jpeg", "gif"])
    {
        if (in_array($type, $img_types)) return true;
        return false;
    }

    // check Allowed and Unauthorized extensions

    private function actImageWatermark($filename)
    {
        $result = array();

        if (isset($this->watermark["enable"]) && $this->watermark["enable"]) {
            foreach ($this->watermark["act"] as $watermark) {
                $logo = $watermark["logo"];
                $w = $watermark["w"];
                $h = $watermark["h"];
                $div = $watermark["div"];
                if (ImageProcess::watermark($this->dirFolder . $this->dirSeparator . $filename, $logo, $h, $w, $div)) {
                    $result[] = true;
                } else {
                    $result[] = false;
                }
            }
        }
        return false;
    }

    // check Allowed size

    private function actImageThumb($filename)
    {
        $result = array();
        if (isset($this->thumbImg["enable"]) && $this->thumbImg["enable"]) {

            $path = $this->thumbImg["path"];
            $path = Dir::ds($path);
            $path = $this->dirFolder . $this->dirSeparator . $path;
            $dir = dirname($path);

            if (!File::make_folder($dir, true)) {
                $this->setError('MKDIR_THUMB', Lang::replace('~upload.err.mkdir', $dir));
            }

            $name = File::name($filename);
            $ext = File::extension($filename);
            $sizes = is_array($this->thumbImg["size"]) ? $this->thumbImg["size"] : explode(',', $this->thumbImg["size"]);

            foreach ($sizes as $size) {
                $result[] = $this->saveThumb($filename, $name, $ext, $size, $path);
            }
        }
        return $result;
    }

    private function saveThumb($filename, $name, $ext, $size, $path)
    {
        $isFix = false;
        if (HelperString::lastHas($size, 'f')) {
            $isFix = true;
            $size = HelperString::lastDelete($size, 'f');
        }

        $new_filename = HelperString::replaceData($path, [
            'name' => $name,
            'ext' => $ext,
            'filename' => $filename,
            'size' => $size,
        ]);

        if (ImageProcess::resize($this->dirFolder . $this->dirSeparator . $filename, $new_filename, $size, $size, $isFix)) {
            $result = $new_filename;
        } else {
            $result = false;
        }
        return $result;
    }

    // get exts and size from setInType()

    private function actImageResize($filename)
    {
        $result = array();
        if (isset($this->resizeImg["enable"]) && $this->resizeImg["enable"]) {
            foreach ($this->resizeImg["act"] as $resize) {
                $dir = $resize["path"];
                $w = $resize["w"];
                $h = $resize["h"];
                $fix = $resize["fix"];
                if (ImageProcess::resize($this->dirFolder . $this->dirSeparator . $filename, $dir . $this->dirSeparator . $filename, $w, $h, $fix)) {
                    $result[] = $dir;
                } else {
                    $result[] = false;
                }
            }
        }
        return $result;
    }

    // Ordering array multi files

    private function actImageConverter($filename)
    {
        if (isset($this->infoConverter["enable"]) && $this->infoConverter["enable"]) {

            $dir = $this->infoConverter["dir"];
            $type = $this->infoConverter["type"];
            $is_old_delete = $this->infoConverter["old_delete"];
            $img = $this->dirFolder . $this->dirSeparator . $filename;
            $img_save = (empty($dir)) ? null : $dir . $this->dirSeparator . $filename;

            if (ImageProcess::converter($img, $type, $img_save, $is_old_delete)) {
                return true;
            }
        }
        return false;
    }

    protected function save($result)
    {
    }

    private function multiUpload()
    {
        $file = $this->getFile;
        $length = count($file);
        if ($this->numLimit != "*")
            $length = ($length < $this->numLimit) ? $length : $this->numLimit;

        $files = array();
        for ($i = 0; $i < $length; $i++) {
            $files[$i] = $this->singleUpload($file[$i]);
        }
        return $files;
    }

    // get allow size according to exts

    public function beforeUpload(\Closure $func)
    {
        $this->beforeFunc = $func;

        return self::$object;
    }

    public function afterUpload(\Closure $func)
    {
        $this->afterFunc = $func;

        return self::$object;
    }

    public function commit()
    {
        $this->isTransaction = false;
        if (count($this->tempFiles) > 0) {
            foreach ($this->tempFiles as $tempFile) {
                $this->dirFolder = $tempFile['folder'];

                if ($this->actUpload($tempFile['tmp'], $tempFile['file'])) {
                    if ($this->isImg($tempFile['type'])) {

                        $this->infoConverter = $tempFile['converter'];
                        $this->watermark = $tempFile['watermark'];
                        $this->thumbImg = $tempFile['thumb'];
                        $this->resizeImg = $tempFile['resize'];

                        if (isset($this->watermark["enable"]) && $this->watermark["enable"]) {
                            $this->actImageWatermark($tempFile['file']);
                        }

                        if (isset($this->thumbImg["enable"]) && $this->thumbImg["enable"]) {
                            $this->actImageThumb($tempFile['file']);
                        }

                        if (isset($this->resizeImg["enable"]) && $this->resizeImg["enable"]) {
                            $this->actImageResize($tempFile['file']);
                        }

                        if (isset($this->infoConverter["enable"]) && $this->infoConverter["enable"]) {
                            $this->actImageConverter($tempFile['file']);
                        }
                    }
                }
            }
        }
        $this->tempFiles = array();
    }
}

