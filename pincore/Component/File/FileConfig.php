<?php

namespace Pinoox\Component\File;

use Pinoox\Portal\App\App;
use Pinoox\Portal\Storage;

class FileConfig
{
    /**
     * @return array{package: string, disk: string, default_access: string, thumb_width: int, thumb_height: int}
     */
    public static function resolve(): array
    {
        $disk = App::get('filesystem.disk')
            ?? App::get('filesystem.default_disk')
            ?? Storage::getDefaultDriver();

        return [
            'package' => (string) (App::get('transport.file') ?? App::package()),
            'disk' => (string) $disk,
            'default_access' => (string) (App::get('filesystem.access') ?? App::get('filesystem.default_access') ?? 'public'),
            'thumb_width' => (int) (App::get('filesystem.thumb_width') ?? 512),
            'thumb_height' => (int) (App::get('filesystem.thumb_height') ?? 512),
        ];
    }
}
