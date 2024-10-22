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

use InvalidArgumentException;
use Pinoox\Component\Database\Model;
use Pinoox\Model\FileModel;
use Pinoox\Portal\FileUploader;

class FileUploaderBuilder
{
    protected string $path;
    protected string $fileInputKey;
    protected string $group;
    protected bool $isThumb = false;
    protected array $allowedExtensions = [];
    protected int $maxFileSize = 0; // Maximum file size in bytes (0 means no limit)
    protected ?Model $model = null; // Model to be updated
    protected string $method = 'update';
    protected string $modelAttributeKey = 'file_id';
    protected string $modelAttributeFileKey = 'file_id';

    /**
     * Set the upload path.
     *
     * @param string $path
     * @return self
     */
    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set the file input key used to retrieve the file from the $_FILES array.
     *
     * @param string $fileInputKey
     * @return self
     */
    public function inputKey(string $fileInputKey): self
    {
        $this->fileInputKey = $fileInputKey;
        return $this;
    }

    /**
     * Set the file group.
     *
     * @param string $group
     * @return self
     */
    public function group(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Enable or disable thumbnail generation.
     *
     * @param bool $isThumb
     * @return self
     */
    public function setThumb(bool $isThumb = true): self
    {
        $this->isThumb = $isThumb;
        return $this;
    }

    /**
     * Set allowed extensions.
     * @param array|string $extensions
     * @return $this
     */
    public function extensions($extensions)
    {
        if (is_string($extensions)) {
            $extensions = array_map('trim', explode(',', $extensions));
        }

        if (!is_array($extensions)) {
            throw new InvalidArgumentException("The extensions must be an array or a comma-separated string.");
        }

        $this->allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Set the maximum allowed file size with a human-readable format (e.g., "5MB").
     *
     * @param string $sizeWithUnit Maximum file size with unit (e.g., "5MB", "500KB").
     * @return self
     * @throws \InvalidArgumentException if the format is invalid.
     */
    public function maxSize(string $sizeWithUnit): self
    {
        $this->maxFileSize = $this->convertToBytes($sizeWithUnit);
        return $this;
    }


    public function modelColumns($primaryKey, $file_key)
    {
        $this->modelAttributeKey = $primaryKey;
        $this->modelAttributeFileKey = $file_key;
        return $this;
    }

    /**
     * Set the model and model attribute for file storage.
     *
     * @param Model|string $model The model class or instance.
     * @param string|null $modelAttributeKey The column where the file ID should be stored.
     * @param string $method The method to use ('update', 'create', or 'updateOrCreate').
     * @return self
     */
    public function model($model, string $modelAttributeKey = null, string $method = 'update'): self
    {
        if (is_string($model) && is_subclass_of($model, Model::class)) {
            $model = new $model();
        }

        if (!$model instanceof Model) {
            throw new InvalidArgumentException("The model must be an instance of Pinoox\Component\Database\Model.");
        }

        $this->method = $method;
        $this->model = $model;
        if ($modelAttributeKey) $this->modelAttributeKey = $modelAttributeKey;

        return $this;
    }

    /**
     * Build the FileUploader instance and optionally update the model.
     * @return \Pinoox\Component\Upload\FileUploader
     * @throws \Exception
     */
    public function upload(): \Pinoox\Component\Upload\FileUploader
    {
        $uploader = FileUploader::store($this->path, $this->fileInputKey)
            ->group($this->group)
            ->insert();

        if ($this->isThumb) {
            $uploader->thumb();
        }

        if (!empty($this->allowedExtensions)) {
            $uploader->setAllowedExtensions($this->allowedExtensions);
        }

        if ($this->maxFileSize > 0) {
            $uploader->setMaxFileSize($this->maxFileSize);
        }

        // Upload the file
        $uploader->upload();

        // If the upload was successful and a model is set, update the model
        if (!$uploader->isFail() && $this->model) {
            $fileId = $uploader->getResult('file_id');

            if ($fileId) {

                $attributes = [$this->modelAttributeFileKey => $fileId];

                // Handle different methods based on the provided method
                switch ($this->method) {
                    case 'update':
                        $modelAttributeValue = $this->model->{$this->modelAttributeKey};
                        $this->model->where($this->modelAttributeKey, $modelAttributeValue)->update($attributes);
                        break;
                    case 'create':
                        $this->model->create($attributes);
                        break;
                    default:
                        $this->model->updateOrCreate(
                            [$this->modelAttributeKey => $fileId],
                            $attributes // Values to update or create
                        );
                }
            }
        } elseif ($uploader->isFail()) {
            throw new \Exception('File upload failed: ' . $uploader->error);
        }

        return $uploader;
    }

    /**
     * Convert human-readable size (e.g., "5MB", "500KB") to bytes.
     *
     * @param string $sizeWithUnit Size with unit (e.g., "5MB", "500KB").
     * @return int Size in bytes.
     * @throws \InvalidArgumentException if the format is invalid.
     */
    protected function convertToBytes(string $sizeWithUnit): int
    {
        $units = ['B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824];
        $sizeWithUnit = strtoupper($sizeWithUnit);
        if (!preg_match('/^(\d+)(B|KB|MB|GB)$/', $sizeWithUnit, $matches)) {
            throw new InvalidArgumentException("Invalid size format: $sizeWithUnit. Use a format like '5MB' or '500KB'.");
        }

        $size = (int)$matches[1];
        $unit = $matches[2];

        return $size * $units[$unit];
    }

    /**
     * Handle the existing file before uploading a new one.
     *
     * @return self
     */
    public function deleteOldFiles(): self
    {
        if ($this->model && $this->model->{$this->modelAttributeKey}) {
            // Delete associated files
            $this->deleteAssociatedFiles($this->model->{$this->modelAttributeKey});
            // Set the model attribute to null
            $this->model->update([$this->modelAttributeKey => null]);
        }
        return $this;
    }

    /**
     * Delete associated files based on file IDs.
     *
     * @param mixed $fileIds Single file ID or array of file IDs.
     * @return void
     */
    private function deleteAssociatedFiles(mixed $fileIds): void
    {
        if (is_array($fileIds)) {
            foreach ($fileIds as $fileId) {
                $this->deleteAssociatedFiles($fileId);
            }
        } else if (!empty($fileIds)) {
            $file = FileModel::find($fileIds);
            if ($file) {
                FileUploader::delete($fileIds);
                $file->delete(); // Delete from database as well
            }
        }
    }

}