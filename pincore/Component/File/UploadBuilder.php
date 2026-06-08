<?php

namespace Pinoox\Component\File;

use InvalidArgumentException;
use Pinoox\Component\Database\Model;
use Pinoox\Component\Upload\FileUploaderFactory;
use Pinoox\Model\FileModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

class UploadBuilder
{
    private string $directory = 'uploads';
    private string $access;
    private ?string $group = null;
    private bool $withThumb = false;
    private int $thumbWidth;
    private int $thumbHeight;
    private bool $saveRecord = true;
    /** @var list<string> */
    private array $allowedExtensions = [];
    private int $maxFileSize = 0;
    private ?array $metadata = null;
    private ?string $hashId = null;
    private ?Model $model = null;
    private string $modelColumn = 'file_id';
    private string $modelKey = 'file_id';
    private string $modelMethod = 'update';
    private bool $deleteOld = false;
    private ?string $disk = null;
    private ?string $package = null;

    public function __construct(
        private readonly UploadedFile|string $source,
        ?array $config = null,
    ) {
        $config ??= FileConfig::resolve();
        $this->access = $config['default_access'];
        $this->thumbWidth = $config['thumb_width'];
        $this->thumbHeight = $config['thumb_height'];
    }

    public function to(string $directory): self
    {
        $this->directory = trim($directory, '/');

        return $this;
    }

    public function access(string $access): self
    {
        $this->access = $access;

        return $this;
    }

    public function group(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function thumb(int $width = 512, int $height = 512): self
    {
        $this->withThumb = true;
        $this->thumbWidth = $width;
        $this->thumbHeight = $height;

        return $this;
    }

    public function maxSize(string|int $size): self
    {
        $this->maxFileSize = is_int($size) ? $size : $this->parseSize((string) $size);

        return $this;
    }

    public function extensions(array|string $extensions): self
    {
        if (is_string($extensions)) {
            $extensions = array_map('trim', explode(',', $extensions));
        }

        if (!is_array($extensions)) {
            throw new InvalidArgumentException('Extensions must be an array or comma-separated string.');
        }

        $this->allowedExtensions = $extensions;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function hashId(string $hashId): self
    {
        $this->hashId = $hashId;

        return $this;
    }

    public function record(bool $save = true): self
    {
        $this->saveRecord = $save;

        return $this;
    }

    public function diskOnly(): self
    {
        return $this->record(false);
    }

    public function attach(Model $model, string $column = 'file_id', string $method = 'update'): self
    {
        $this->model = $model;
        $this->modelColumn = $column;
        $this->modelMethod = $method;

        return $this;
    }

    public function disk(?string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function package(?string $package): self
    {
        $this->package = $package;

        return $this;
    }

    public function replaceOn(Model $model, string $column = 'file_id'): self
    {
        $this->attach($model, $column);
        $this->deleteOld = true;

        return $this;
    }

    public function save(): UploadResult
    {
        if ($this->deleteOld && $this->model && $this->model->{$this->modelColumn}) {
            (new Manager())->remove((int) $this->model->{$this->modelColumn});
            $this->model->{$this->modelColumn} = null;
        }

        $options = array_filter([
            'disk' => $this->disk,
            'package' => $this->package,
        ]);

        $uploader = (new FileUploaderFactory())->store(
            trim($this->directory, '/') . '/',
            self::resolveSource($this->source),
            $this->access,
            $options !== [] ? $options : null,
        );

        if ($this->group !== null) {
            $uploader->group($this->group);
        }

        if ($this->saveRecord) {
            $uploader->insert();
        }

        if ($this->withThumb) {
            $uploader->thumb($this->thumbWidth, $this->thumbHeight);
        }

        if ($this->allowedExtensions !== []) {
            $uploader->setAllowedExtensions($this->allowedExtensions);
        }

        if ($this->maxFileSize > 0) {
            $uploader->setMaxFileSize($this->maxFileSize);
        }

        if ($this->hashId !== null) {
            $uploader->setHashId($this->hashId);
        }

        if ($this->metadata !== null) {
            $uploader->setMetaData($this->metadata);
        }

        $uploader->upload();

        if ($uploader->isFail()) {
            return UploadResult::fail($uploader->error);
        }

        if (!$this->saveRecord) {
            return UploadResult::disk((string) $uploader->getResult('file'));
        }

        $fileId = (int) $uploader->getResult('file_id');
        $record = FileModel::find($fileId);

        if (!$record) {
            return UploadResult::fail('file_record_missing');
        }

        if ($this->model) {
            $this->persistOnModel($fileId);
        }

        return UploadResult::ok($record, (string) $uploader->getResult('file'));
    }

    private function persistOnModel(int $fileId): void
    {
        if (!$this->model) {
            return;
        }

        $attributes = [$this->modelColumn => $fileId];

        match ($this->modelMethod) {
            'create' => $this->model->create($attributes),
            'updateOrCreate' => $this->model->updateOrCreate(
                [$this->modelKey => $this->model->{$this->modelKey} ?? $fileId],
                $attributes,
            ),
            default => $this->model
                ->where($this->modelKey, $this->model->{$this->modelKey})
                ->update($attributes),
        };
    }

    private function parseSize(string $sizeWithUnit): int
    {
        $units = ['B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824];
        $sizeWithUnit = strtoupper(trim($sizeWithUnit));

        if (!preg_match('/^(\d+)(B|KB|MB|GB)$/', $sizeWithUnit, $matches)) {
            throw new InvalidArgumentException("Invalid size format: {$sizeWithUnit}. Use values like 2MB or 500KB.");
        }

        return (int) $matches[1] * $units[$matches[2]];
    }

    /**
     * Resolve form field name to UploadedFile when needed.
     */
    public static function resolveSource(UploadedFile|string $source): UploadedFile|string
    {
        if ($source instanceof UploadedFile) {
            return $source;
        }

        if (!empty($_FILES)) {
            $file = (new FileBag($_FILES))->get($source);
            if ($file instanceof UploadedFile) {
                return $file;
            }
        }

        return $source;
    }
}

