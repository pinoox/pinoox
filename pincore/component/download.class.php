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

class Download
{
    private static $downloadObj = null;
    private static $savePath = null;
    private $filename = null;
    private $fileSize = 0;
    private $mimeType = 'application/octet-stream';
    private $charset = null;
    # the offset to the start in multi Threaded downloading
    private $pathFile = null;
    private $isMultiThreaded = true;
    private $mtOffset = 0;
    private $isAttachment = true;
    private $transferEncoding = 'binary';
    private $errs = array();
    private $isLimit = false;
    private $limit = 0;
    private $isFetch = false;
    private $timeout = 0;
    private $http = [];

    /*
     *
     * class download :
     *
     * ------------------ Syntax ----------------------
     * $download = new Download(<path (string)>,<isAttachment (bool)>,<isMultiThreaded (string)>);
     * $download->setFilename(<name (string)>);
     * $download->setLimit(<speed (int)>,<type (string)>);
     * $download->setContentType(<mimeType (string)>,<charset (string)>);
     * $download->setTransferEncoding(<type (string)>);
     * $download->process();
     *
     * ----------------- Examples ---------------------
     *
     * Example 1)
     * download easy file
     *
     * $download = new Download(path);
     * $download->process();
     *
     * #########################
     * Example 2)
     * download easy file with new Name
     *
     * $download = new Download(path);
     * $download->setFilename('test.zip');
     * $download->process();
     *
     * #########################
     * Example 3)
     * download file Of a zip(mimeType)
     *
     * $download = new Download(path);
     * $download->setContentType('application/zip');
     * $download->process();
     *
     * #########################
     * Example 4)
     * show & download inline file for example picture
     *
     * $download = new Download(path,false);
     * $download->process();
     *
     * ##########################
     * Example 5)
     * download file without resume download
     *
     * $download = new Download(path,true,false);
     * $download->process();
     *
     * ##########################
     * Example 6)
     * download file with 100 KB limit speed
     *
     * $download = new Download(path);
     * $download->setLimit(100);
     * $download->process();
     *
     * ##########################
     * Example 7)
     * download file with 1 MB limit speed
     *
     * $download = new Download(path);
     * $download->setLimit(1,'MB');
     * $download->process();
     *
     * ##########################
     * Example 8)
     * show html file
     *
     * $download = new Download(path,false);
     * $download->setLimit(1,'MB');
     * $download->setContentType('text/html','utf-8');
     * $download->setTransferEncoding('8bit');
     * $download->process();
     *
     * etc
     */

    function __construct($pathFile, $isAttachment = true, $isMultiThreaded = true, $isFetch = false, $size = 0)
    {
        if (!$isFetch && !is_file($pathFile)) {
            $this->errs['path'] = 'no file';
            return;
        }
        $this->pathFile = $pathFile;
        if ($size > 0) {
            $this->fileSize = $size;
        } else {
            if ($isFetch)
                $this->fileSize = File::get_remote_file_size($pathFile);
            else
                $this->fileSize = File::file_size($pathFile);
        }
        $this->isAttachment = $isAttachment;
        $this->isMultiThreaded = $isMultiThreaded;
        $this->isFetch = $isFetch;
    }

    public static function init($pathFile, $isAttachment = true, $isMultiThreaded = true)
    {
        self::$downloadObj = new Download($pathFile, $isAttachment, $isMultiThreaded);
        return self::$downloadObj;
    }

    public static function fetch($link, $savePath = null, $size = 0, $isMultiThreaded = true)
    {
        self::$savePath = $savePath;
        self::$downloadObj = new Download($link, false, $isMultiThreaded, true, $size);
        return self::$downloadObj;
    }

    public function setContentType($mimeType = 'application/octet-stream', $charset = null)
    {
        $this->mimeType = $mimeType;
        $this->charset = $charset;
        return self::$downloadObj;
    }

    public function setFilename($name)
    {
        $this->filename = $name;
        return self::$downloadObj;
    }

    public function timeout($second)
    {
        $this->timeout = $second;
        return self::$downloadObj;
    }

    public function http($data)
    {
        $this->http = $data;
        return self::$downloadObj;
    }

    public function setLimit($speed, $type = "KB")
    {
        $this->isLimit = true;
        $this->limit = File::convert_size($speed, $type, 'B');
        return self::$downloadObj;
    }

    public function setTransferEncoding($type = 'binary')
    {
        $this->transferEncoding = $type;
        return self::$downloadObj;
    }

    public function process()
    {
        if (!empty($this->errs)) return false;


        if ($this->isLimit)
            $chunkSize = $this->limit;
        else
            $chunkSize = 1 * (1024 * 1024);

        $bytesSend = 0;
        $result = '';


        $http = $this->http;
        $http['user_agent'] = $_SERVER['HTTP_USER_AGENT'] . ', pinoox';

        if ($this->timeout > 0) $http['timeout'] = $this->timeout;
        $context = stream_context_create(['http' => $http]);

        if ($file = @fopen($this->pathFile, 'r', false, $context)) {
            $path_save = null;
            if (!empty(self::$savePath) && $this->isFetch) {
                $dir = File::dir_file(self::$savePath);
                File::make_folder($dir,true);
                $path_save = fopen(self::$savePath, 'w');
            }

            $size = $this->getSizeAndLoadHeaders();
            if (isset($_SERVER['HTTP_RANGE']) && $this->isMultiThreaded)
                fseek($file, $this->mtOffset);

            //write the data out
            while (!feof($file) && !connection_aborted() && $bytesSend < $size) {
                if (!empty(self::$savePath) && $this->isFetch) {
                    $buffer = stream_copy_to_stream($file, $path_save);
                } else {
                    $buffer = fread($file, $chunkSize);

                    if ($this->isFetch)
                        $result .= $buffer;
                    else
                        echo $buffer;

                }


                if (!$this->isFetch)
                    flush();

                if ($this->isLimit) sleep(1);

                if (is_numeric($buffer))
                    $bytesSend += $buffer;
                else
                    $bytesSend += strlen($buffer);
            }

            fclose($file);
        }
        return $result;
    }

    private function getSizeAndLoadHeaders()
    {
        $size = $this->fileSize;

        // disable output buffering
        @ob_end_clean();
        // disable execution time limit
        set_time_limit(0);
        // required for IE, otherwise Content-Disposition may be ignored
        if (ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
        if (!$this->isFetch) {

            HelperHeader::contentType($this->mimeType, $this->charset);
            HelperHeader::contentDisposition($this->filename, $this->isAttachment);
            HelperHeader::contentTransferEncoding($this->transferEncoding);
            HelperHeader::acceptRanges('bytes');
            // clear cache
            HelperHeader::cacheControl('private');
            HelperHeader::pragma('private');
            HelperHeader::expires('0');
        }

        // multipart-download and download resuming support
        if (isset($_SERVER["HTTP_RANGE"]) && $this->isMultiThreaded) {
            list($size_unit, $range) = explode("=", $_SERVER["HTTP_RANGE"], 2);
            if ($size_unit == 'bytes') {
                list($range) = explode(",", $range, 2);
                list($range_start, $range_end) = explode("-", $range);
                $range_start = round(floatval($range_start), 0);
                if (!$range_end) $range_end = $size - 1;
                else $range_end = round(floatval($range_end), 0);
                $partial_length = $range_end - $range_start + 1;
                if (!$this->isFetch) {
                HelperHeader::generateStatusCodeHTTP('206 Partial Content');
                HelperHeader::contentRange('bytes', $range_start . '-' . $range_end, $size);
                }
                $this->mtOffset = $range_start;
            } else {
                $partial_length = $size;
            }
        } else {
            $partial_length = $size;
        }

        if (!$this->isFetch) {
            HelperHeader::contentLength($partial_length);
        }

        return $partial_length;
    }

}