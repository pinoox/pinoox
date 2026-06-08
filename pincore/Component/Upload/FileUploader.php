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

use Illuminate\Filesystem\FilesystemAdapter;
use Pinoox\Component\File\FileConfig;
use Pinoox\Component\File\FileStorage;
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
    private int $maxFileSize = 0;
    private string $package;
    private string $diskName;
    private FilesystemAdapter $disk;

    public function __construct(
        string $destination = '',
        UploadedFile|string $fileKey = null,
        string $access = 'public',
        ?string $package = null,
        ?string $disk = null,
    ) {
        $config = FileConfig::resolve();
        $this->destination = trim($destination, '/');
        $this->access = $access;
        $this->package = $package ?? $config['package'];
        $this->diskName = $disk ?? $config['disk'];
        $this->disk = FileStorage::disk($this->package, $this->diskName);

        if ($fileKey instanceof UploadedFile) {
            $this->fileKey = $fileKey;
            $this->file = $fileKey;
        } else {
            $this->fileKey = $fileKey;
            if (!empty($_FILES) && !empty($fileKey)) {
                $this->file = (new FileBag($_FILES))->get($fileKey);
            }
        }
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

        $this->extension = strtolower($this->file->getClientOriginalExtension());
        $this->size = (int) $this->file->getSize();
        $this->fileRealname = $this->file->getClientOriginalName();
        $this->filename = Str::generateLowRandom(16) . '.' . $this->extension;

        if (!empty($this->allowedExtensions) && !in_array($this->extension, $this->allowedExtensions, true)) {
            $this->error = 'invalid_extension';

            return $this;
        }

        if ($this->maxFileSize > 0 && $this->size > $this->maxFileSize) {
            $this->error = 'file_too_large';

            return $this;
        }

        $mimetype = $this->file->getMimeType();
        $storageKey = FileStorage::key($this->destination, $this->filename);
        $visibility = FileStorage::visibility($this->access);

        $stored = $this->disk->putFileAs(
            $this->destination,
            $this->file,
            $this->filename,
            ['visibility' => $visibility],
        );

        if ($stored === false) {
            $this->error = 'upload_failed';

            return $this;
        }

        $this->result = [
            'filename' => $this->filename,
            'realname' => $this->fileRealname,
            'extension' => $this->extension,
            'mimetype' => $mimetype,
            'size' => $this->size,
            'path' => $this->destination,
            'file' => $storageKey,
            'disk' => $this->diskName,
            'package' => $this->package,
        ];

        if ($this->isInsert) {
            $this->callEvents(Event::Insert);
        }

        if ($this->isThumb) {
            $this->createThumbnail($storageKey);
        }

        return $this;
    }

    public function delete(FileModel $fileModel): static
    {
        $this->fileModel = $fileModel;
        $this->callEvents(Event::Delete);

        return $this;
    }

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
        $metadata = $this->metadata ?? [];

        if (!is_array($metadata)) {
            $metadata = [];
        }

        $metadata['disk'] = $this->diskName;
        $metadata['package'] = $this->package;

        return $metadata;
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

    public function getFileModel(): FileModel
    {
        return $this->fileModel;
    }

    public function getFileRealname(): string
    {
        return $this->fileRealname;
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

    public function getGroup(): string
    {
        return $this->group;
    }

    public function isFail()
    {
        return !empty($this->error);
    }

    public function getDiskName(): string
    {
        return $this->diskName;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    private function isImage(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    private function createThumbnail(string $storageKey): void
    {
        if (!$this->isImage()) {
            return;
        }

        $thumbKey = FileStorage::thumbKey($this->destination, $this->filename);
        $thumbDir = trim(dirname($thumbKey), '/');

        if ($thumbDir !== '' && $thumbDir !== '.') {
            $this->disk->makeDirectory($thumbDir);
        }

        $source = $this->file->getPathname();
        $tempThumb = tempnam(sys_get_temp_dir(), 'pin_thumb_');

        Image::read($source)
            ->scale($this->thumbInfo['width'], $this->thumbInfo['height'])
            ->save($tempThumb);

        $this->disk->put(
            $thumbKey,
            (string) file_get_contents($tempThumb),
            ['visibility' => FileStorage::visibility($this->access)],
        );

        if (is_file($tempThumb)) {
            unlink($tempThumb);
        }
    }
}

