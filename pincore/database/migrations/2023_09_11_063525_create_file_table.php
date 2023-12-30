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

return new class extends MigrationBase
{
	/**
	 * Run the migrations.
	 */
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create('pincore_file', function (Blueprint $table) {
            $table->increments('file_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('app', 255)->nullable();
            $table->string('file_group', 255)->nullable();
            $table->string('file_realname', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_ext', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->double('file_size')->nullable();
            $table->dateTime('file_date')->nullable();
            $table->string('file_access', 255)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->foreign('user_id')->references('user_id')->on('pincore_user')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->schema->table('pincore_file', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        $this->schema->dropIfExists('pincore_file');
    }
};
