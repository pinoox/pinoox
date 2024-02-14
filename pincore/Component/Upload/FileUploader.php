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

use Illuminate\Database\Eloquent\Builder;
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
    private string $group;
    private static array $events;
    private $result;
    private $isInsert;
    private FileModel $fileModel;
    private $isThumb;
    private $thumbInfo;

    public function __construct(string $path = '', string $destination = '', string $fileKey = null, string $access = 'public')
    {
        $this->path = $path;
        $this->destination = $destination;
        $this->access = $access;
        $this->fileKey = $fileKey;

        if (!empty($_FILES) && !empty($fileKey))
            $this->file = (new FileBag($_FILES))->get($fileKey);

        $this->setUploadPath();
    }

    public function insert(): self
    {
        $this->isInsert = true;
        return $this;
    }

    public function group($group): self
    {
        $this->group = $group;
        return $this;
    }

    public function thumb($width = 100, $height = 100): self
    {
        $this->isThumb = true;
        $this->thumbInfo['width'] = $width;
        $this->thumbInfo['height'] = $height;
        return $this;
    }

    public function upload(): self
    {
        $this->extension = $this->file->getClientOriginalExtension();
        $this->size = $this->file->getSize();
        $this->fileRealname = $this->file->getClientOriginalName();
        $this->filename = Str::generateLowRandom(16) . '.' . $this->extension;

        $this->file->move($this->getUploadPath(), $this->filename);

        if ($this->isInsert) {
            $this->callEvents(Event::Insert);
        }

        if ($this->isThumb) {
            $this->createThumbnail();
        }

        return $this;
    }

    public function delete(FileModel $fileModel): self
    {
        $this->fileModel = $fileModel;
        $path = path('~apps/' . $fileModel->file_path);
        $originalFile = $path . '/' . $fileModel->file_name;
        $thumbnailFile = $path . '/thumbs/thumb_' . $fileModel->file_name;

        if (file_exists($originalFile)) unlink($originalFile);
        if (file_exists($thumbnailFile)) unlink($thumbnailFile);

        $this->callEvents(Event::Delete);

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

    public function setFile(UploadedFile $file): void
    {
        $this->file = $file;
    }


    public function getAccess(): string
    {
        return $this->access;
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
        self::$events[Event::getName($type)][] = $event;
    }

    public function callEvents(Event $type, array $params = []): void
    {
        foreach (self::$events[Event::getName($type)] ?? [] as $event) {
            $event($this, ...$params);
        }
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

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getFileRealname(): string
    {
        return $this->fileRealname;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    private function setUploadPath(): void
    {
        $this->uploadPath = $this->path . '/' . $this->destination;
    }

    public function getResult($key = null): mixed
    {
        return $this->result[$key] ?? $this->result;
    }

    public function setResult($key, $value): void
    {
        $this->result[$key] = $value;
    }

    private function isImage(): bool
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
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
                ->resize($this->thumbInfo['width'], $this->thumbInfo['height'])
                ->save($thumbnailPath);
        }
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

}
