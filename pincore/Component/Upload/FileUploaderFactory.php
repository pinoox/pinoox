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


use Pinoox\Model\FileModel;
use Pinoox\Portal\App\App;

class FileUploaderFactory
{

    public function store($destination, $file, $access = 'public'): FileUploader
    {
        return new FileUploader(
            path(''),
            $destination,
            $file,
            $access
        );
    }

    public function delete(int $file_id): FileUploader|bool
    {
        $fileModel = FileModel::find($file_id);
        if (empty($fileModel)) return false;

        return (new FileUploader())->delete($fileModel);
    }

    public function addEvent(Event $type, \Closure $event): void
    {
        FileUploader::addEvent($type, $event);
    }

}