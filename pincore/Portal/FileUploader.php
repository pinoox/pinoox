<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Carbon\Carbon;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Upload;
use Pinoox\Component\Upload\FileUploader as ObjectPortal2;
use Pinoox\Component\Upload\FileUploaderFactory as ObjectPortal1;
use Pinoox\Component\User;
use Pinoox\Model\FileModel;
use Pinoox\Portal\App\App;

/**
 * @method static ObjectPortal2 store($destination, $file, $access = 'public')
 * @method static \Pinoox\Component\Upload\FileUploader|bool delete(int $file_id)
 * @method static FileUploader addEvent(\Pinoox\Component\Upload\Event $type, \Closure $event)
 * @method static \Pinoox\Component\Upload\FileUploaderFactory ___()
 *
 * @see \Pinoox\Component\Upload\FileUploaderFactory
 */
class FileUploader extends Portal
{
	public static function __register(): void
	{
		self::__bind(ObjectPortal1::class)
		    ->addMethodCall('addEvent', [
		        Upload\Event::Insert,
		        function (ObjectPortal2 $uploader) {
		            self::insertFileModel($uploader);
		        }
		    ])->addMethodCall('addEvent', [
		        Upload\Event::Delete,
		        function (ObjectPortal2 $uploader) {
		            self::deleteFileModel($uploader);
		        }
		    ]);
	}


	private static function insertFileModel(ObjectPortal2 $uploader): void
	{
		$model = FileModel::create([
		    'user_id' => User::get('user_id'),
		    'app' => App::package(),
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


	private static function deleteFileModel(ObjectPortal2 $uploader): void
	{
		FileModel::where('file_id', $uploader->getFileModel()->file_id)->delete();
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'file.uploader';
	}


	/**
	 * Get exclude method names .
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [
		    'setDestination',
		    'setFile'
		];
	}
}
