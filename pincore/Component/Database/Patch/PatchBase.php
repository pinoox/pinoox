<?php

namespace Pinoox\Component\Database\Patch;

use Illuminate\Database\Schema\Builder;
use Pinoox\Portal\Database\DB;

abstract class PatchBase
{
    protected Builder $schema;
    protected string $package;

    public function __construct(string $package)
    {
        $this->schema = DB::schema();
        $this->package = $package;
    }

    abstract public function run(): void;

    protected function getPackage(): string
    {
        return $this->package;
    }

    protected function getSchema(): Builder
    {
        return $this->schema;
    }
}
