<?php
/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

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
        // Your patch logic here
    }

    public function down(): void
    {
        // Optional rollback logic here. Return true from canRollback() when used.
    }
};
