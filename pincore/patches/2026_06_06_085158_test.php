<?php

namespace Pinoox\Patches;

use Pinoox\Component\Database\Patch\PatchBase;

return new class extends PatchBase
{
    public function description(): string
    {
        return 'for test';
    }

    public function shouldRun(): bool
    {
        return true;
    }

    public function canRollback(): bool
    {
        return false;
    }

    public function run(): void
    {
    }

    public function down(): void
    {
    }
};
