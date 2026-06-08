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

use Pinoox\Component\File\FileConfig;
use Pinoox\Model\FileModel;

class FileUploaderFactory
{
    /**
     * @param array{package?: string, disk?: string}|null $options
     */
    public function store($destination, $file, $access = 'public', ?array $options = null): FileUploader
    {
        $config = FileConfig::resolve();
        $options ??= [];

        return new FileUploader(
            destination: trim((string) $destination, '/'),
            fileKey: $file,
            access: $access,
            package: $options['package'] ?? $config['package'],
            disk: $options['disk'] ?? $config['disk'],
        );
    }

    public function delete(int $file_id): FileUploader|bool
    {
        $fileModel = FileModel::find($file_id);
        if (empty($fileModel)) {
            return false;
        }

        return (new FileUploader())->delete($fileModel);
    }

    public function addEvent(Event $type, \Closure $event): void
    {
        FileUploader::addEvent($type, $event);
    }
}

