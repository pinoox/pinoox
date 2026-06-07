<?php

namespace Pinoox\Component\Migration;

use Illuminate\Database\Schema\ForeignKeyDefinition;
use Pinoox\Portal\Database\DB;

class MigrationForeignKeyDefinition extends ForeignKeyDefinition
{
    public function on($table)
    {
        if (is_array($table)) {
            [$name, $package] = [$table[0] ?? '', $table[1] ?? null];
            $table = DB::raw(DB::physicalTableName($name, $package));
        }

        return $this->__call('on', [$table]);
    }
}

