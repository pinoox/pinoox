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

return new class extends MigrationBase {
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create(Table::FILE, function (Blueprint $table) {
            $table->increments('file_id');
            $table->string('hash_id', 50)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('app', 255)->nullable();
            $table->string('file_group', 255)->nullable();
            $table->string('file_realname', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_ext', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->double('file_size')->nullable();
            $table->string('file_access', 255)->nullable();
            $table->json('file_metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->foreign('user_id')->references('user_id')->on(Table::USER)->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->schema->table(Table::FILE, function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        $this->schema->dropIfExists(Table::FILE);
    }
};
