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
    protected string $fileIdAttribute = 'file_id';
    protected int $maxFileSize = 0; // Maximum file size in bytes (0 means no limit)
    protected ?Model $model = null; // Model to be updated
    protected string $modelAttribute = 'file_id'; // Attribute in the model to store file ID
    private string $mediaIdColumn;


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
            // Split the string into an array
            $extensions = array_map('trim', explode(',', $extensions));
        }

        if (!is_array($extensions)) {
            throw new InvalidArgumentException("The extensions must be an array or a comma-separated string.");
        }

        // Assign the extensions to the uploader
        $this->allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Set the model attribute where the file ID will be stored.
     *
     * @param string $fileIdAttribute The model attribute to store the file ID.
     * @return self
     */
    public function setFileIdAttribute(string $fileIdAttribute): self
    {
        $this->fileIdAttribute = $fileIdAttribute;
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

    /**
     * Set the model for the uploader.
     * @param string|Model $model
     * @param string $mediaIdColumn
     * @return $this
     */
    public function model($model, $mediaIdColumn)
    {
        if (is_string($model) && is_subclass_of($model,  Model::class)) {
            // Instantiate the model if the class name is provided
            $model = new $model();
        }

        if (!$model instanceof  Model) {
            throw new InvalidArgumentException("The model must be an instance of Pinoox\Component\Database\Model.");
        }

        // Assign the model and media ID column to the uploader
        $this->model = $model;
        $this->mediaIdColumn = $mediaIdColumn;
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
                $this->model->update([$this->modelAttribute => $fileId]);
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
            throw new \InvalidArgumentException("Invalid size format: $sizeWithUnit. Use a format like '5MB' or '500KB'.");
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
        if ($this->model && $this->model->{$this->modelAttribute}) {
            // Delete associated files
            $this->deleteAssociatedFiles($this->model->{$this->modelAttribute});
            // Set the model attribute to null
            $this->model->update([$this->modelAttribute => null]);
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