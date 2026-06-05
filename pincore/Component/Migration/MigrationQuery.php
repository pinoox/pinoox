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

namespace Pinoox\Component\Migration;

use Pinoox\System\Model\MigrationModel;
use Pinoox\System\Model\Table;
use Pinoox\Portal\Database\DB;

class MigrationQuery
{
    private static function tableExists(): bool
    {
        return DB::schema('pincore')->hasTable(DB::tableName(Table::MIGRATION, 'pincore'));
    }

    public static function fetchLatestBatch($app): int
    {
        if (!self::tableExists()) {
            return 0;
        }
        return MigrationModel::where('app', $app)->orderBy('batch', 'DESC')->first()->batch ?? 0;
    }

    public static function fetchAllByBatch($batch, $app): ?array
    {
        if (!self::tableExists()) {
            return [];
        }
        if (!empty($batch)) {
            return MigrationModel::query()->where('batch', $batch)->where('app', $app)->get()->toArray() ?? null;
        } else {
            return MigrationModel::query()->where('app', $app)->get()->toArray() ?? null;
        }
    }

    public static function insert($fileName, $app, $batch)
    {
        if (!self::tableExists()) {
            return null;
        }
        
        $data = [
            'migration' => $fileName,
            'batch' => $batch + 1,
            'app' => $app,
        ];
        
        return MigrationModel::create($data);
    }

    public static function is_exists($migration, $app)
    {
        if (!self::tableExists()) {
            return false;
        }
        return MigrationModel::where([
            ['migration', '=', $migration],
            ['app', '=', $app],
        ])->exists();
    }

    public static function delete($migration, $app)
    {
        if (!self::tableExists()) {
            return false;
        }
        return MigrationModel::where([
            ['migration', '=', $migration],
            ['app', '=', $app],
        ])->delete();
    }
}
