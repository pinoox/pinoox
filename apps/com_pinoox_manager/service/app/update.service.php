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

namespace App\com_pinoox_manager\Service\app;

use App\com_pinoox_manager\Component\Wizard;
use Pinoox\Component\Config;
use Pinoox\Component\Dir;
use Pinoox\Component\File;
use Pinoox\Component\Interfaces\ServiceInterface;
use Pinoox\Component\User;
use Pinoox\Model\PinooxDatabase;
use Pinoox\Model\UserModel;

class UpdateService implements ServiceInterface
{

    public function _run()
    {
        Config::remove('options.pinoox_auth');
        Config::save('options');

        $dir = Dir::path('pinupdate/','com_pinoox_manager');
        if(!is_dir($dir))
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

    private static function runQuery($appDB)
    {
        if (is_file($appDB)) {
            $package_name = 'com_pinoox_manager';

            $prefix = Config::get('~database.prefix');
            $query = file_get_contents($appDB);
            $query = str_replace('{dbprefix}', $prefix . $package_name . '_', $query);
            $queryArr = explode(';', $query);

            PinooxDatabase::$db->startTransaction();
            foreach ($queryArr as $q) {
                if (empty($q)) continue;
                PinooxDatabase::$db->mysqli()->query($q);
            }

            PinooxDatabase::$db->commit();

            File::remove_file($appDB);

            return true;
        }
        return false;
    }

    public function _stop()
    {
    }
}
