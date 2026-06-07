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

use Pinoox\System\Model\HistoryModel;
use Pinoox\System\Model\Table;
use Pinoox\Portal\Database\DB;

class MigrationQuery
{
    public const TYPE_MIGRATION = 'migration';
    public const TYPE_PATCH = 'patch';

    private static function tableExists(): bool
    {
        return DB::schema('pincore')->hasTable(DB::tableName(Table::HISTORY, 'pincore'));
    }

    public static function fetchLatestBatch($app): int
    {
        if (!self::tableExists()) {
            return 0;
        }
        return HistoryModel::where('type', self::TYPE_MIGRATION)
                ->where('app', $app)
                ->orderBy('batch', 'DESC')
                ->first()->batch ?? 0;
    }

    public static function fetchAllByBatch($batch, $app): ?array
    {
        if (!self::tableExists()) {
            return [];
        }
        if (!empty($batch)) {
            return HistoryModel::query()
                    ->where('type', self::TYPE_MIGRATION)
                    ->where('batch', $batch)
                    ->where('app', $app)
                    ->get()
                    ->toArray() ?? null;
        } else {
            return HistoryModel::query()
                    ->where('type', self::TYPE_MIGRATION)
                    ->where('app', $app)
                    ->get()
                    ->toArray() ?? null;
        }
    }

    public static function insert($fileName, $app, $batch)
    {
        if (!self::tableExists()) {
            return null;
        }
        
        $data = [
            'type' => self::TYPE_MIGRATION,
            'migration' => $fileName,
            'batch' => $batch + 1,
            'app' => $app,
        ];
        
        return HistoryModel::create($data);
    }

    public static function is_exists($migration, $app)
    {
        if (!self::tableExists()) {
            return false;
        }
        return HistoryModel::where('type', self::TYPE_MIGRATION)
            ->where('migration', $migration)
            ->where('app', $app)
            ->exists();
    }

    public static function delete($migration, $app)
    {
        if (!self::tableExists()) {
            return false;
        }
        return HistoryModel::where('type', self::TYPE_MIGRATION)
            ->where('migration', $migration)
            ->where('app', $app)
            ->delete();
    }

    public static function importLegacyMigrationRecords(): int
    {
        if (!self::tableExists() || !self::legacyTableExists()) {
            return 0;
        }

        $records = DB::table(DB::tableName('migration', 'pincore'), null, 'pincore')->get();
        $imported = 0;

        foreach ($records as $record) {
            $migration = $record->migration ?? null;
            $app = $record->app ?? null;

            if (empty($migration) || empty($app) || self::is_exists($migration, $app)) {
                continue;
            }

            HistoryModel::create([
                'type' => self::TYPE_MIGRATION,
                'migration' => $migration,
                'batch' => $record->batch ?? 1,
                'app' => $app,
            ]);

            $imported++;
        }

        return $imported;
    }

    private static function legacyTableExists(): bool
    {
        return DB::schema('pincore')->hasTable(DB::tableName('migration', 'pincore'));
    }
}
