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

namespace pinoox\app\com_pinoox_manager\service\app;

use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\interfaces\ServiceInterface;

class UpdateService implements ServiceInterface
{

    public function _run()
    {
        $dir = Dir::path('pinupdate');
        if(!file_exists($dir))
            return;

        $pinoox_version_code = config('~pinoox.version_code');
        $files = File::get_files_by_pattern($dir, '*.db');

        foreach ($files as $file) {
            $version_code = File::name($file);
            if ($pinoox_version_code <= $version_code) {
                $this->runQuery($file);
            }
        }

        File::remove($dir);
    }

    private function runQuery($file)
    {
        Wizard::runQuery($file, 'com_pinoox_manager', true, false);
    }

    public function _stop()
    {
    }
}