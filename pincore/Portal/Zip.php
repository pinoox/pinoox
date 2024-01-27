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

use PhpZip\Model\ZipEntry as ObjectPortal1;
use PhpZip\Model\ZipEntryMatcher as ObjectPortal2;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\Response as ObjectPortal3;
use Pinoox\Component\Source\Portal;

/**
 * @method static ZipFile openFile(string $filename, array $options = [])
 * @method static ZipFile openFromString(string $data, array $options = [])
 * @method static ZipFile openFromStream($handle, array $options = [])
 * @method static array getListFiles()
 * @method static int count()
 * @method static ?string getArchiveComment()
 * @method static ZipFile setArchiveComment(?string $comment = NULL)
 * @method static bool hasEntry(string $entryName)
 * @method static ObjectPortal1 getEntry(string $entryName)
 * @method static bool isDirectory(string $entryName)
 * @method static string getEntryComment(string $entryName)
 * @method static ZipFile setEntryComment(string $entryName, ?string $comment = NULL)
 * @method static string getEntryContents(string $entryName)
 * @method static getEntryStream(string $entryName)
 * @method static ObjectPortal2 matcher()
 * @method static array getEntries()
 * @method static ZipFile extractTo(string $destDir, $entries = NULL, array $options = [], ?array &$extractedEntries = [])
 * @method static ZipFile addFromString(string $entryName, string $contents, ?int $compressionMethod = NULL)
 * @method static array addFromFinder(\Symfony\Component\Finder\Finder $finder, array $options = [])
 * @method static ObjectPortal1 addSplFile(\SplFileInfo $file, ?string $entryName = NULL, array $options = [])
 * @method static ZipFile addFile(string $filename, ?string $entryName = NULL, ?int $compressionMethod = NULL)
 * @method static ZipFile addFromStream($stream, string $entryName, ?int $compressionMethod = NULL)
 * @method static ZipFile addEmptyDir(string $dirName)
 * @method static ZipFile addDir(string $inputDir, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static ZipFile addDirRecursive(string $inputDir, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static ZipFile addFilesFromIterator(\Iterator $iterator, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static ZipFile addFilesFromGlob(string $inputDir, string $globPattern, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static ZipFile addFilesFromGlobRecursive(string $inputDir, string $globPattern, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static ZipFile addFilesFromRegex(string $inputDir, string $regexPattern, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static ZipFile addFilesFromRegexRecursive(string $inputDir, string $regexPattern, string $localPath = '/', ?int $compressionMethod = NULL)
 * @method static Zip addAll(array $mapData)
 * @method static ZipFile rename(string $oldName, string $newName)
 * @method static ZipFile deleteFromName(string $entryName)
 * @method static ZipFile deleteFromGlob(string $globPattern)
 * @method static ZipFile deleteFromRegex(string $regexPattern)
 * @method static ZipFile deleteAll()
 * @method static ZipFile setCompressionLevel(int $compressionLevel = 5)
 * @method static ZipFile setCompressionLevelEntry(string $entryName, int $compressionLevel)
 * @method static ZipFile setCompressionMethodEntry(string $entryName, int $compressionMethod)
 * @method static ZipFile setReadPassword(string $password)
 * @method static ZipFile setReadPasswordEntry(string $entryName, string $password)
 * @method static ZipFile setPassword(string $password, ?int $encryptionMethod = 1)
 * @method static ZipFile setPasswordEntry(string $entryName, string $password, ?int $encryptionMethod = NULL)
 * @method static ZipFile disableEncryption()
 * @method static ZipFile disableEncryptionEntry(string $entryName)
 * @method static ZipFile unchangeAll()
 * @method static ZipFile unchangeArchiveComment()
 * @method static ZipFile unchangeEntry($entry)
 * @method static ZipFile saveAsFile(string $filename)
 * @method static ZipFile saveAsStream($handle)
 * @method static Zip outputAsAttachment(string $outputFilename, ?string $mimeType = NULL, bool $attachment = true)
 * @method static \Psr\Http\Message\ResponseInterface outputAsResponse(\Psr\Http\Message\ResponseInterface $response, string $outputFilename, ?string $mimeType = NULL, bool $attachment = true)
 * @method static \Psr\Http\Message\ResponseInterface outputAsPsr7Response(\Psr\Http\Message\ResponseInterface $response, string $outputFilename, ?string $mimeType = NULL, bool $attachment = true)
 * @method static ObjectPortal3 outputAsSymfonyResponse(string $outputFilename, ?string $mimeType = NULL, bool $attachment = true)
 * @method static string outputAsString()
 * @method static Zip close()
 * @method static ZipFile rewrite()
 * @method static Zip offsetSet($offset, $value)
 * @method static Zip offsetUnset($offset)
 * @method static ?string current()
 * @method static ?string offsetGet($offset)
 * @method static ?string key()
 * @method static Zip next()
 * @method static bool valid()
 * @method static bool offsetExists($offset)
 * @method static Zip rewind()
 * @method static \PhpZip\ZipFile ___()
 *
 * @see \PhpZip\ZipFile
 */
class Zip extends Portal
{
	public static function __register(): void
	{
		self::__bind(ZipFile::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'zip';
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
			'addAll',
			'outputAsAttachment',
			'close',
			'offsetSet',
			'offsetUnset',
			'next',
			'rewind'
		];
	}
}
