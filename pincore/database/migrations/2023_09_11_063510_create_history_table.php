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
        $this->schema->create($this->table(Table::HISTORY, 'platform'), function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('migration')->index();
            $table->string('migration');
            $table->integer('batch');
            $table->string('app');
            $table->string('status')->default('success')->index();
            $table->string('checksum')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('error')->nullable();
            $table->text('metadata')->nullable();
            $table->index(['type', 'app']);
        });

    }

    public function down()
    {
        $this->schema->dropIfExists($this->table(Table::HISTORY, 'platform'));
    }
};
