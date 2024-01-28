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

namespace Pinoox\Database\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Pinoox\Component\Migration\MigrationBase;

return new class extends MigrationBase {
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create('pincore_user', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('session_id')->nullable();
            $table->unsignedInteger('avatar_id')->nullable();
            $table->string('app', 255)->nullable();
            $table->string('fname', 255)->nullable();
            $table->string('lname', 255)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->dateTime('register_date')->nullable();
            $table->string('status', 255)->nullable();

            $table->index('user_id');
            $table->index('avatar_id');
            $table->foreign('avatar_id')->references('file_id')->on('pincore_file')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->schema->getConnection()->statement('SET FOREIGN_KEY_CHECKS = 0');
        $this->schema->dropIfExists('pincore_user');
        $this->schema->getConnection()->statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
