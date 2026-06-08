<?php

namespace Pinoox\Component\File;

use Illuminate\Filesystem\FilesystemAdapter;
use Pinoox\Component\File\FileStorage;
use Pinoox\Model\FileModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Manager
{
    public function upload(UploadedFile|string $source): UploadBuilder
    {
        return new UploadBuilder(UploadBuilder::resolveSource($source));
    }

    public function find(int $fileId): ?FileModel
    {
        return FileModel::where('file_id', $fileId)->first();
    }

    public function remove(int $fileId): bool
    {
        return (bool) (new FileUploaderFactory())->delete($fileId);
    }

    public function url(int|FileModel|null $file): ?string
    {
        $record = $this->resolve($file);

        return $record?->file_link;
    }

    public function thumb(int|FileModel|null $file): ?string
    {
        $record = $this->resolve($file);

        return $record?->thumb_link;
    }

    /**
     * @return array<string, mixed>
     */
    public function info(int|FileModel|null $file): array
    {
        $record = $this->resolve($file);
        if (!$record) {
            return [];
        }

        return [
            'file_id' => $record->file_id,
            'file_group' => $record->file_group,
            'file_realname' => $record->file_realname,
            'file_name' => $record->file_name,
            'file_ext' => $record->file_ext,
            'file_path' => $record->file_path,
            'file_size' => $record->file_size,
            'file_access' => $record->file_access,
            'file_metadata' => $record->file_metadata ?? [],
            'url' => $record->file_link,
            'thumb' => $record->thumb_link,
            'created_at' => $record->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return list<FileModel>
     */
    public function listByGroup(string $group): array
    {
        return FileModel::where('file_group', $group)->get()->all();
    }

    public function attach(int $fileId, object $model, string $column): bool
    {
        if (!method_exists($model, 'update')) {
            return false;
        }

        $key = method_exists($model, 'getKeyName') ? $model->getKeyName() : 'id';

        return (bool) $model->where($key, $model->{$key})->update([$column => $fileId]);
    }

    public function setPackage(string $package): void
    {
        FileModel::setPackage($package);
    }

    public function storage(?string $disk = null): FilesystemAdapter
    {
        return FileStorage::disk(null, $disk);
    }

    private function resolve(int|FileModel|null $file): ?FileModel
    {
        if ($file instanceof FileModel) {
            return $file;
        }

        if (is_int($file)) {
            return $this->find($file);
        }

        return null;
    }
}

