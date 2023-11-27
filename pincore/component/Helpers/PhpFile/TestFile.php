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


namespace pinoox\component\Helpers\PhpFile;

namespace pinoox\component\Helpers\PhpFile;

use pinoox\component\File;

class TestFile extends PhpFile
{
    public static function create(string $path, string $testName): bool
    {
        $source = <<<EOT
<?php

it('do something', function () {
    // Arrange

    // Act

    // Assert
});
EOT;


        return File::generate($path, $source);
    }

}