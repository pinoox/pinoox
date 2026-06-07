<?php

namespace Pinoox\Component\Migration;

use Illuminate\Database\Schema\Blueprint;

class MigrationBlueprint extends Blueprint
{
    public function foreign($columns, $name = null)
    {
        $command = new MigrationForeignKeyDefinition(
            $this->indexCommand('foreign', $columns, $name)->getAttributes()
        );
        $this->commands[count($this->commands) - 1] = $command;
        return $command;
    }
}

