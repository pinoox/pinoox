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

namespace Pinoox\Component\Upload;

use Pinoox\Component\Helpers\Str;
use Pinoox\Model\FileModel;
use Pinoox\Portal\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;


class FileUploader
{
    private string $filename;
    private string $fileRealname;
    private string $extension;
    private string $destination;
    private int $size;
    private string $path;
    private string $uploadPath;
    private UploadedFile|null $file;
    private string|null $fileKey;
    private string $access;
    private ?string $hashId = null;
    private ?array $metadata = null;
    private string $group;
    private static array $events;
    private $result;
    private $isInsert;
    private FileModel $fileModel;
    private $isThumb;
    private $thumbInfo;
    public $error;

    private array $allowedExtensions = [];
    private int $maxFileSize = 0;// Maximum file size in bytes


    public function __construct(string $path = '', string $destination = '', UploadedFile|string $fileKey = null, string $access = 'public')
    {
        $this->path = $path;
        $this->destination = $destination;
        $this->access = $access;

        if ($fileKey instanceof UploadedFile) {
            $this->fileKey = $fileKey;
            $this->file = $fileKey;
        } else {
            $this->fileKey = $fileKey;
            if (!empty($_FILES) && !empty($fileKey))
                $this->file = (new FileBag($_FILES))->get($fileKey);
        }


        $this->setUploadPath();
    }

    public function insert(): static
    {
        $this->isInsert = true;
        return $this;
    }

    public function group($group): static
    {
        $this->group = $group;
        return $this;
    }

    public function thumb($width = 512, $height = 512): static
    {
        $this->isThumb = true;
        $this->thumbInfo['width'] = $width;
        $this->thumbInfo['height'] = $height;
        return $this;
    }

    public function upload(): static
    {
        if (empty($this->file)) {
            $this->error = -1;
            return $this;
        }
 
        $this->extension = $this->file->getClientOriginalExtension();
        $this->size = $this->file->getSize();
        $this->fileRealname = $this->file->getClientOriginalName();
        $this->filename = Str::generateLowRandom(16) . '.' . $this->extension;

        // Check if the file extension is allowed
        if (!empty($this->allowedExtensions) && !in_array($this->extension, $this->allowedExtensions)) {
            $this->error = 'invalid_extension'; // Set custom error for invalid extension
            return $this;
        }

        // Check if the file size exceeds the maximum allowed size
        if ($this->maxFileSize > 0 && $this->size > $this->maxFileSize) {
            $this->error = 'file_too_large'; // Set custom error for file size limit
            return $this;
        }

        $mimetype = $this->file->getMimeType();

        // Move the uploaded file to the destination
        $this->file->move($this->getUploadPath(), $this->filename);

        $this->result = [
            'filename' => $this->filename,
            'realname' => $this->fileRealname,
            'extension' => $this->extension,
            'mimetype' => $mimetype,
            'size' => $this->size,
            'path' => $this->getUploadPath(),
            'file' => $this->getUploadPath() . $this->filename,
        ];

        if ($this->isInsert) {
            $this->callEvents(Event::Insert);
        }

        if ($this->isThumb) {
            $this->createThumbnail();
        }

        return $this;
    }

    public function delete(FileModel $fileModel): static
    {
        $this->fileModel = $fileModel;
        $path = path($fileModel->file_path, $fileModel->app);
        $originalFile = $path . '/' . $fileModel->file_name;
        $thumbnailFile = $path . '/thumbs/thumb_' . $fileModel->file_name;

        if (file_exists($originalFile)) unlink($originalFile);
        if (file_exists($thumbnailFile)) unlink($thumbnailFile);

        $this->callEvents(Event::Delete);

        return $this;
    }

    /**
     * Set the maximum allowed file size in bytes.
     *
     * @param int $maxFileSize
     * @return static
     */
    public function setMaxFileSize(int $maxFileSize): static
    {
        $this->maxFileSize = $maxFileSize;
        return $this;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    public function setFile(UploadedFile $file): static
    {
        $this->file = $file;

        return $this;
    }


    public function getAccess(): string
    {
        return $this->access;
    }

    public function getHashId(): ?string
    {
        return $this->hashId;
    }

    public function getMetaData(): ?array
    {
        return $this->metadata;
    }

    public function setHashId($hash_id): static
    {
        $this->hashId = $hash_id;

        return $this;

    }

    public function setMetaData($metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public static function addEvent(Event $type, \Closure $event): void
    {
        static::$events[Event::getName($type)][] = $event;
    }

    public function callEvents(Event $type, array $params = []): static
    {
        foreach (static::$events[Event::getName($type)] ?? [] as $event) {
            $event($this, ...$params);
        }

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFileModel(): FileModel
    {
        return $this->fileModel;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getFileRealname(): string
    {
        return $this->fileRealname;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    private function setUploadPath(): static
    {
        $this->uploadPath = $this->path . '/' . $this->destination;

        return $this;
    }

    public function getResult($key = null): mixed
    {
        return $this->result[$key] ?? $this->result;
    }

    public function setResult($key, $value): static
    {
        $this->result[$key] = $value;

        return $this;
    }

    private function isImage(): bool
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower($this->file->getClientOriginalExtension());

        return in_array($fileExtension, $allowedExtensions);
    }

    private function createThumbnail(): void
    {
        if ($this->isImage()) {
            $originalImage = $this->getUploadPath() . '/' . $this->filename;
            $thumbnailFolder = $this->getUploadPath() . '/thumbs/';
            $thumbnailName = 'thumb_' . $this->filename;
            $thumbnailPath = $thumbnailFolder . $thumbnailName;

            if (!file_exists($thumbnailFolder))
                mkdir($thumbnailFolder, 0755, true);

            Image::read($originalImage)
                ->scale($this->thumbInfo['width'], $this->thumbInfo['height'])
                ->save($thumbnailPath);
        }
    }

    /**
     * Set the allowed file extensions for upload.
     *
     * @param array $extensions List of allowed extensions.
     * @return static
     */
    public function setAllowedExtensions(array $extensions): static
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    public function isFail()
    {
        return !empty($this->error);
    }

}
