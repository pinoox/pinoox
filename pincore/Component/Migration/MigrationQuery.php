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

use Pinoox\Model\HistoryModel;
use Pinoox\Model\Table;
use Pinoox\Portal\Database\DB;

class MigrationQuery
{

    public const TYPE_MIGRATION = 'migration';

    public const TYPE_PATCH = 'patch';

    private static function tableExists(): bool
    {
        return DB::schema('platform')->hasTable(DB::tableName(Table::HISTORY, 'platform'));
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

        $records = DB::table(DB::tableName('migration', 'platform'), null, 'platform')->get();
        $imported = 0;

        foreach ($records as $record) {
            $migration = $record->migration ?? null;
            $app = $record->app ?? null;

            if (empty($migration) || empty($app) || self::is_exists($migration, $app)) {
                continue;
            }

            if (!self::legacyMigrationHasAppliedTables($migration)) {
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
        return DB::schema('platform')->hasTable(DB::tableName('migration', 'platform'));
    }

    private static function legacyMigrationHasAppliedTables(string $migration): bool
    {
        if (str_contains($migration, 'create_history_table') || str_contains($migration, 'create_migration_table')) {
            return self::tableExists();
        }

        if (str_contains($migration, 'create_access_tables')) {
            return self::physicalTableExists(Table::ROLE);
        }

        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration) ?? $migration;

        if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
            return self::physicalTableExists($matches[1]);
        }

        return false;
    }

    private static function physicalTableExists(string $table): bool
    {
        try {
            $connection = DB::connection('platform');
            $physical = DB::physicalTableName($table, 'platform');
            $database = (string) $connection->getDatabaseName();

            if ($database === '' || $physical === '') {
                return false;
            }

            $row = $connection->selectOne(
                'SELECT 1 AS found FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1',
                [$database, $physical],
            );

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }
}

