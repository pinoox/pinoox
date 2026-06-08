<?php

namespace Pinoox\Portal;

use Pinoox\Component\File\Manager;
use Pinoox\Component\File\UploadBuilder;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Upload;
use Pinoox\Component\Upload\FileUploader as StorageEngine;
use Pinoox\Component\File\FileStorage;
use Pinoox\Model\FileModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * File upload and storage management (`pinx_file`).
 *
 * @method static UploadBuilder upload(UploadedFile|string $source)
 * @method static FileModel|null find(int $fileId)
 * @method static bool remove(int $fileId)
 * @method static string|null url(int|FileModel|null $file)
 * @method static string|null thumb(int|FileModel|null $file)
 * @method static array info(int|FileModel|null $file)
 * @method static list<FileModel> listByGroup(string $group)
 * @method static bool attach(int $fileId, object $model, string $column)
 * @method static void setPackage(string $package)
 * @method static \Illuminate\Filesystem\FilesystemAdapter storage(?string $disk = null)
 * @method static Manager ___()
 *
 * @see Manager
 */
class File extends Portal
{
    public static function __register(): void
    {
        self::__bind(Manager::class);

        StorageEngine::addEvent(Upload\Event::Insert, function (StorageEngine $uploader) {
            self::insertRecord($uploader);
        });

        StorageEngine::addEvent(Upload\Event::Delete, function (StorageEngine $uploader) {
            self::deleteRecord($uploader);
        });
    }

    private static function insertRecord(StorageEngine $uploader): void
    {
        $model = FileModel::create([
            'user_id' => Auth::id(),
            'app' => $uploader->getPackage(),
            'file_name' => $uploader->getFilename(),
            'file_realname' => $uploader->getFileRealname(),
            'file_ext' => $uploader->getExtension(),
            'file_path' => $uploader->getDestination(),
            'file_size' => $uploader->getSize(),
            'file_access' => $uploader->getAccess(),
            'hash_id' => $uploader->getHashId(),
            'file_metadata' => $uploader->getMetaData(),
            'file_group' => $uploader->getGroup(),
        ]);

        if ($model) {
            $uploader->setResult('file_id', $model->file_id);
        }
    }

    private static function deleteRecord(StorageEngine $uploader): void
    {
        FileModel::where('file_id', $uploader->getFileModel()->file_id)->delete();
    }

    public static function __name(): string
    {
        return 'file';
    }

    public static function __exclude(): array
    {
        return [];
    }

    public static function __callback(): array
    {
        return [];
    }
}

