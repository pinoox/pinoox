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

namespace pinoox\component\wizard;

use PhpZip\Exception\ZipEntryNotFoundException;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use pinoox\component\kernel\Exception;
use pinoox\portal\Zip;

/**
 * Pinoox Wizard
 *
 * Provides a base class for handling package installation and extraction.
 */
abstract class Wizard
{
    /**
     * @var string The path to the package file
     */
    protected string $path;

    /**
     * @var string The filename of the package file
     */
    protected string $filename;

    /**
     * @var string The package name
     */
    protected string $package;

    /**
     * @var string The package type ('app' or 'template')
     */
    protected string $type;

    /**
     * @var array The list of errors
     */
    protected array $errors;

    /**
     * @var string The root path for temporary files
     */
    protected string $tmpPathRoot = PINOOX_CORE_PATH . 'pinker' . DS . 'wizard_tmp';

    /**
     * @var string The path to the package directory
     */
    protected string $packagePath;

    /**
     * @var string The temporary path for the package extraction
     */
    protected string $tmpPathPackage;

    /**
     * @var array|null The package information
     */
    protected ?array $info;

    /**
     * @var bool Indicates if the package is an update
     */
    protected bool $isUpdate = false;

    /**
     * @var bool Install the package even if it is already installed
     */
    protected bool $force = false;

    /**
     * @var ZipFile The ZipFile instance for the package
     */
    protected ZipFile $zip;

    /**
     * Initializes the path and performs validation.
     *
     * @param string $path The path to the package file
     * @throws Exception
     */
    private function initPath(string $path): void
    {
        $this->path = $path;

        if (!$this->isExists()) {
            throw new Exception($this->getErrors(true));
        }

        $this->filename = basename($this->path);

        $this->createTmp();
    }

    /**
     * Sets the package name based on the package information.
     */
    protected function setPackage(): void
    {
        $this->package = $this->info['package'];
        $this->packagePath = PINOOX_APP_PATH . $this->package . DS;
    }

    /**
     * Opens the package file for extraction and validation.
     *
     * @param string $path The path to the package file
     * @return Wizard The Wizard instance
     * @throws Exception
     */
    public function open(string $path): Wizard
    {
        $this->initPath($path);

        $this->zip = Zip::openFile($this->path);

        $targetFile = $this->targetFile();
        $this->hasEntry($targetFile);

        return $this;
    }

    /**
     * Extracts specific files from the package to the temporary directory.
     *
     * @param string ...$files The files to extract
     * @return void
     */
    protected function extractTemp(string ...$files): void
    {
        Zip::extractTo($this->tmpPathPackage, $files);
    }

    /**
     * Retrieves the package information.
     *
     * @return array|null The package information array or null if not available
     */
    abstract public function getInfo(): ?array;

    /**
     * Sets an error and throws an exception.
     *
     * @param string $error The error message
     * @throws Exception
     */
    protected function setError(string $error): void
    {
        $this->errors[] = $error;
        throw new Exception($error);
    }

    /**
     * Retrieves the list of errors.
     *
     * @param bool $last Whether to return the last error only
     * @return mixed The list of errors or false if not available
     */
    public function getErrors(bool $last = false): mixed
    {
        if (!isset($this->errors)) {
            return false;
        }
        if ($last) {
            return end($this->errors);
        }
        return $this->errors;
    }

    /**
     * Creates the temporary directory for package extraction.
     *
     * @return void
     */
    private function createTmp(): void
    {
        if (!is_dir($this->tmpPathRoot)) {
            mkdir($this->tmpPathRoot);
        }
        $this->tmpPathPackage = $this->tmpPathRoot . DS . basename($this->filename, '.pin');
        if (!is_dir($this->tmpPathPackage)) {
            mkdir($this->tmpPathPackage);
        }
    }

    /**
     * Retrieves metadata about the package.
     *
     * @return array The metadata array
     * @throws ZipEntryNotFoundException
     */
    public function getMeta(): array
    {
        $entry = $this->zip->getEntry($this->targetFile());
        return [
            'filename' => $entry->getName(),
            'filesCount' => $this->zip->count(),
            'compressedSize' => $entry->getCompressedSize(),
            'uncompressedSize' => $entry->getUncompressedSize(),
            'time' => $entry->getATime(),
        ];
    }

    /**
     * Checks if the package file exists.
     *
     * @return bool True if the package file exists, false otherwise
     * @throws Exception
     */
    private function isExists(): bool
    {
        if (!file_exists($this->path)) {
            $this->setError('Package not found: "' . $this->path . '"');
            return false;
        }
        return true;
    }

    /**
     * Retrieves the target file based on the package type.
     *
     * @return string The target file
     */
    protected function targetFile(): string
    {
        return $this->type === 'app' ? 'app.php' : 'meta.json';
    }

    /**
     * Checks if the package contains the specified entry.
     *
     * @param string $targetFile The target entry file
     * @return void
     * @throws Exception
     */
    private function hasEntry(string $targetFile): void
    {
        $has = $this->zip->hasEntry($targetFile);

        if (!$has) {
            $this->setError("Doesn't exist '" . $targetFile . "' inside the package!");
        }
    }

    /**
     * Checks if the package is an update.
     *
     * @return bool True if the package is an update, false otherwise
     */
    protected function checkUpdate(): bool
    {
        return $this->isUpdate = file_exists(PINOOX_APP_PATH . $this->package);
    }

    /**
     * Extracts the package to the specified path.
     *
     * @param string $path The path to extract the package to
     * @return ZipFile The ZipFile instance for further operations
     * @throws ZipException
     */
    protected function extract(string $path): ZipFile
    {
        if (!is_dir($path)) mkdir($path, 0777, true);

        return $this->zip->extractTo($path)->deleteFromRegex('~^\..~');
    }

    /**
     * Retrieves the package information from an existing package.
     *
     * @return bool|array False if the package is not valid, otherwise the package information array
     * @throws Exception
     */
    protected function getExistsPackageInfo(): bool|array
    {
        $existsInfo = include PINOOX_APP_PATH . $this->package . DS . $this->targetFile();
        if (empty($existsInfo)) {
            $this->setError('The package is not valid because there is no essential file inside (Doesn\'t exist "' . $this->targetFile() . '" in "' . $this->package . '")');
            return false;
        }
        return $existsInfo;
    }

    /**
     * Loads the target file from the temporary package directory.
     *
     * @return void
     */
    protected function loadTargetFileFromPin(): void
    {
        if ($this->type == 'template') {
            $this->info = json_decode(file_get_contents($this->tmpPathPackage . DS . $this->targetFile()), true);
        } else {
            $this->info = include $this->tmpPathPackage . DS . $this->targetFile();
        }

        $this->setPackage();

        $this->checkUpdate();
    }

    /**
     * Enable force mode for installing packages.
     */
    public function force(bool $val = true): Wizard
    {
        $this->force = $val;
        return $this;
    }
}
