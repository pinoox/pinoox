<?php

namespace Pinoox\Component\File;

use Pinoox\Model\FileModel;

readonly class UploadResult
{
    public function __construct(
        public bool $success,
        public ?int $id = null,
        public ?string $url = null,
        public ?string $thumb = null,
        public ?string $path = null,
        public ?FileModel $record = null,
        public mixed $error = null,
    ) {
    }

    public static function ok(FileModel $record, ?string $path = null): self
    {
        return new self(
            success: true,
            id: (int) $record->file_id,
            url: $record->file_link,
            thumb: $record->thumb_link,
            path: $path,
            record: $record,
        );
    }

    public static function disk(string $absolutePath): self
    {
        return new self(
            success: true,
            path: $absolutePath,
        );
    }

    public static function fail(mixed $error): self
    {
        return new self(success: false, error: $error);
    }
}

