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

use pinoox\component\helpers\HelperHeader;

/**
 * Download & fetch file
 *
 * ----------------- Examples ---------------------
 *
 * Example 1)
 * download file
 *
 * $download = new Download(path);
 * $download->process();
 * or
 * Download::init(path)->process();
 *
 * #########################
 * Example 2)
 * download file with new Name
 *
 * $download = new Download(path);
 * $download->setFilename('test.zip');
 * $download->process();
 *
 * #########################
 * Example 3)
 * download file In a way zip file
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
 */
class Download
{
    /**
     * An instance of Download Class
     *
     * @var Download|null
     */
    private static $downloadObj = null;

    /**
     * Path directory for save file
     *
     * @var string|null
     */
    private static $savePath = null;

    /**
     * Filename for download
     *
     * @var string|null
     */
    private $filename = null;

    /**
     * Size file for download
     *
     * @var float|int|string
     */
    private $fileSize = 0;

    /**
     * mime-type send data for download
     *
     * @var string
     */
    private $mimeType = 'application/octet-stream';

    /**
     * Charset send data for download
     *
     * @var string|null
     */
    private $charset = null;

    /**
     * Path file for download
     *
     * @var null
     */
    private $pathFile = null;

    /**
     * Enable multi-threaded downloading
     *
     * @var bool
     */
    private $isMultiThreaded = true;

    /**
     * The offset to the start in multi-threaded downloading
     *
     * @var int
     */
    private $mtOffset = 0;

    /**
     * Enable download file In a way attachment
     *
     * @var bool
     */
    private $isAttachment = true;

    /**
     * Transfer-encoding send data for download
     *
     * @var string
     */
    private $transferEncoding = 'binary';

    /**
     * Error messages
     *
     * @var array
     */
    private $errs = array();

    /**
     * Enable limit speed in download
     *
     * @var bool
     */
    private $isLimit = false;

    /**
     * Limit speed in download
     *
     * @var int
     */
    private $limit = 0;

    /**
     * Enable fetch file of URL
     *
     * @var bool
     */
    private $isFetch = false;

    /**
     * Timeout for request fetch
     *
     * @var int
     */
    private $timeout = 0;

    /**
     * Information HTTP for request fetch
     *
     * @var array
     */
    private $http = [];

    /**
     * Size partial length file
     *
     * @var string|int
     */
    private $partial_length;

    /**
     * Download constructor
     *
     * @param string $pathFile
     * @param bool $isAttachment
     * @param bool $isMultiThreaded
     * @param bool $isFetch
     * @param int $size
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
                $this->fileSize = File::size($pathFile);
        }
        $this->isAttachment = $isAttachment;
        $this->isMultiThreaded = $isMultiThreaded;
        $this->isFetch = $isFetch;
    }

    /**
     * The instance an object of download class
     *
     * @param string $pathFile
     * @param bool $isAttachment
     * @param bool $isMultiThreaded
     * @return Download
     */
    public static function init($pathFile, $isAttachment = true, $isMultiThreaded = true)
    {
        self::$downloadObj = new Download($pathFile, $isAttachment, $isMultiThreaded);
        return self::$downloadObj;
    }

    /**
     * The instance an object of download class for fetching file
     *
     * @param string $link
     * @param string|null $savePath
     * @param int $size
     * @param bool $isMultiThreaded
     * @return Download
     */
    public static function fetch($link, $savePath = null, $size = 0, $isMultiThreaded = true)
    {
        self::$savePath = $savePath;
        self::$downloadObj = new Download($link, false, $isMultiThreaded, true, $size);
        return self::$downloadObj;
    }

    /**
     * Set mime-type & charset content for download
     *
     * @param string $mimeType
     * @param string|null $charset
     * @return Download
     */
    public function setContentType($mimeType = 'application/octet-stream', $charset = null)
    {
        $this->mimeType = $mimeType;
        $this->charset = $charset;
        return self::$downloadObj;
    }

    /**
     * Set filename for download
     *
     * @param string $name
     * @return Download
     */
    public function setFilename($name)
    {
        $this->filename = $name;
        return self::$downloadObj;
    }

    /**
     * Set timeout for request fetch
     *
     * @param int $second
     * @return Download
     */
    public function timeout($second)
    {
        $this->timeout = $second;
        return self::$downloadObj;
    }

    /**
     * Set data HTTP for request fetch
     *
     * @param array $data
     * @return Download
     */
    public function http($data)
    {
        $this->http = $data;
        return self::$downloadObj;
    }

    /**
     * Set limit speed in download
     *
     * @param int $speed
     * @param string $type
     * @return Download
     */
    public function setLimit($speed, $type = "KB")
    {
        $this->isLimit = true;
        $this->limit = File::convert_size($speed, $type, 'B');
        return self::$downloadObj;
    }

    /**
     * Set Transfer-encoding content for download
     *
     * @param string $type
     * @return Download
     */
    public function setTransferEncoding($type = 'binary')
    {
        $this->transferEncoding = $type;
        return self::$downloadObj;
    }

    /**
     * Process download
     *
     * @return bool|string|mixed
     */
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
                $dir = File::dir(self::$savePath);
                File::make_folder($dir, true);
                $path_save = fopen(self::$savePath, 'w');
            }

            $this->adjustPrerequisites();
            $size = $this->partial_length;
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

    /**
     * Adjust prerequisites for download
     */
    private function adjustPrerequisites()
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

        $this->partial_length = $partial_length;
    }

}