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

namespace pinoox\database\migrations;

use Illuminate\Database\Schema\Blueprint;
use pinoox\component\migration\MigrationBase;

return new class extends MigrationBase
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create('pincore_token', function (Blueprint $table) {
            $table->string('token_key', 255);
            $table->string('token_name', 255)->nullable();
            $table->text('token_data')->nullable();
            $table->string('app', 255);
            $table->unsignedInteger('user_id')->nullable();
            $table->string('ip', 255)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('insert_date')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->string('remote_url', 255)->nullable();

            $table->primary(['token_key', 'app']);
            $table->index('user_id');
            $table->foreign('user_id')->references('user_id')->on('pincore_user')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->schema->table('pincore_token', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        $this->schema->dropIfExists('pincore_token');
    }
};
