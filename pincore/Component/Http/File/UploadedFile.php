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


namespace Pinoox\Component\Http\File;

use Pinoox\Component\Upload\FileUploader;
use Pinoox\Component\Upload\FileUploaderFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile as UploadedFileSymfony;

class UploadedFile extends UploadedFileSymfony
{

    public function store($destination, $access = 'public'): FileUploader
    {
        return (new FileUploaderFactory())->store($destination, $this, $access);
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