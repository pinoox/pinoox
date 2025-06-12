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

namespace Pinoox\Database\migrations;

use Illuminate\Database\Schema\Blueprint;
use Pinoox\Component\Migration\MigrationBase;
use Pinoox\Model\Table;

return new class extends MigrationBase
{
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create(Table::MIGRATION, function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
            $table->string('app');
        });

    }

    public function down()
    {
        $this->schema->dropIfExists(Table::MIGRATION);
    }
};
