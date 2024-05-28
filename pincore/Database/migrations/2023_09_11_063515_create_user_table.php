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
        $this->schema->create(Table::USER, function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('session_id')->nullable();
            $table->unsignedInteger('avatar_id')->nullable();
            $table->string('app', 50)->nullable();
            $table->string('fname', 50)->nullable();
            $table->string('lname', 50)->nullable();
            $table->string('username', 50)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('group_key', 50)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('avatar_id');
            $table->foreign('avatar_id')->references('file_id')->on(Table::FILE)->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->schema->getConnection()->statement('SET FOREIGN_KEY_CHECKS = 0');
        $this->schema->dropIfExists(Table::USER);
        $this->schema->getConnection()->statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
