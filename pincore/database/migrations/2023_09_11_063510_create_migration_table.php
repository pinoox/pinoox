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

namespace pinoox\app\com_pinoox_test\database\migrations;

use Illuminate\Database\Schema\Blueprint;
use pinoox\component\migration\MigrationBase;

return new class extends MigrationBase
{
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create('pincore_migration', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
            $table->string('app');
        });

    }

    public function down()
    {
        $this->schema->dropIfExists('pincore_migration');
    }
};
