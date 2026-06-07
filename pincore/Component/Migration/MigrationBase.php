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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Database\DB;

class MigrationBase extends Migration
{
    public Builder $schema;

    private static ?string $package = null;

    public static function usePackage(?string $package): void
    {
        self::$package = $package;
    }

    public function __construct()
    {
        $package = self::$package ?? App::package();
        $this->schema = DB::schema(DB::connectionNameForPackage($package));
        $this->schema->blueprintResolver(fn($table, $callback, $prefix) => new MigrationBlueprint($table, $callback, $prefix));
    }

    protected function table(string $name, ?string $package = null): string
    {
        return DB::tableName($name, $package ?? self::$package ?? App::package());
    }

    protected function foreignTable(string $name, ?string $package = null): Expression
    {
        return DB::raw(DB::physicalTableName($name, $package ?? self::$package ?? App::package()));
    }

}

