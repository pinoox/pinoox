<?php

namespace Pinoox\Component\Http\File;

use Pinoox\Component\File\UploadBuilder;
use Pinoox\Portal\File;
use Symfony\Component\HttpFoundation\File\UploadedFile as UploadedFileSymfony;

class UploadedFile extends UploadedFileSymfony
{
    public function store(string $destination, string $access = 'public'): UploadBuilder
    {
        return File::upload($this)->to($destination)->access($access);
    }

    public static function createFromBase($file, $test = false)
    {
        if (is_array($file)) {
            return new static($file['tmp_name'], $file['name'], $file['type'], $file['error'], false);
        }

        return $file instanceof static ? $file : new static(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getError(),
            $test
        );
    }
}

