<?php

namespace Pinoox\Patches;

use Pinoox\Component\Database\Patch\PatchBase;
use Pinoox\Model\Table;
use Pinoox\Portal\Database\DB;
use Pinoox\Support\Platform;

return new class extends PatchBase
{
    /** @var list<string> */
    private array $tables = [
        Table::USER,
        Table::TOKEN,
        Table::FILE,
        Table::ROLE,
        Table::PERMISSION,
        Table::HISTORY,
    ];

    public function description(): string
    {
        return 'Rename platform scope column app value from pincore to platform';
    }

    public function shouldRun(): bool
    {
        foreach ($this->tables as $table) {
            $physical = DB::physicalTableName($table, Platform::PACKAGE);

            if (!DB::schema(Platform::PACKAGE)->hasTable($physical)) {
                continue;
            }

            if (DB::connection(Platform::PACKAGE)->table($physical)->where('app', 'pincore')->exists()) {
                return true;
            }
        }

        return false;
    }

    public function canRollback(): bool
    {
        return true;
    }

    public function run(): void
    {
        $connection = DB::connection(Platform::PACKAGE);

        foreach ($this->tables as $table) {
            $physical = DB::physicalTableName($table, Platform::PACKAGE);

            if (!DB::schema(Platform::PACKAGE)->hasTable($physical)) {
                continue;
            }

            $connection->table($physical)
                ->where('app', 'pincore')
                ->update(['app' => Platform::PACKAGE]);
        }
    }

    public function down(): void
    {
        $connection = DB::connection(Platform::PACKAGE);

        foreach ($this->tables as $table) {
            $physical = DB::physicalTableName($table, Platform::PACKAGE);

            if (!DB::schema(Platform::PACKAGE)->hasTable($physical)) {
                continue;
            }

            $connection->table($physical)
                ->where('app', Platform::PACKAGE)
                ->update(['app' => 'pincore']);
        }
    }
};
