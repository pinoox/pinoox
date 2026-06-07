<?php

namespace Pinoox\Component\Database\Patch;

use Illuminate\Database\Schema\Builder;
use Pinoox\Portal\Database\DB;

abstract class PatchBase
{
    protected Builder $schema;
    protected string $package = '';

    public function __construct(string $package = '')
    {
        $this->schema = DB::schema();
        $this->package = $package;
    }

    public function run(): void
    {
        $this->up();
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }

    public function canRollback(): bool
    {
        return false;
    }

    public function shouldRun(): bool
    {
        return true;
    }

    public function description(): string
    {
        return '';
    }

    public function metadata(): array
    {
        return [];
    }

    public function setPackage(string $package): static
    {
        $this->package = $package;

        return $this;
    }

    protected function getPackage(): string
    {
        return $this->package;
    }

    protected function getSchema(): Builder
    {
        return $this->schema;
    }
}

