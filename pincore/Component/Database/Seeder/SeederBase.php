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

namespace Pinoox\Component\Database\Seeder;

use Illuminate\Database\Schema\Builder;
use Pinoox\Portal\Database\DB;

abstract class SeederBase
{
    protected Builder $schema;
    protected string $package;

    public function __construct(string $package)
    {
        $this->schema = DB::schema();
        $this->package = $package;
    }

    /**
     * Run the database seeds.
     */
    abstract public function run(): void;

    /**
     * Call other seeders.
     */
    protected function call(array $seeders): void
    {
        foreach ($seeders as $seeder) {
            $instance = new $seeder($this->package);
            $instance->run();
        }
    }

    /**
     * Get the package name.
     */
    protected function getPackage(): string
    {
        return $this->package;
    }

    /**
     * Get the schema builder instance.
     */
    protected function getSchema(): Builder
    {
        return $this->schema;
    }
} 