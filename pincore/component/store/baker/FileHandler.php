<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */


namespace pinoox\component\store\baker;


use pinoox\component\File;

/**
 * Class to handle file operations
 */

/**
 * Class to handle file operations
 */
class FileHandler implements FileHandlerInterface
{
    public function store(string $file, $data): void
    {
        File::generate($file, $data);
    }

    public function retrieve(string $file)
    {
        if (is_file($file)) {
            return include $file;
        }

        return null;
    }

    public function remove(string $file): void
    {
        File::remove_file($file);
    }

}