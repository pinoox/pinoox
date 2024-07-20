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
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->schema->disableForeignKeyConstraints();
        $this->schema->create(Table::TOKEN, function (Blueprint $table) {
            $table->increments('token_id');
            $table->string('token_key', 100);
            $table->string('token_name', 255)->nullable();
            $table->json('token_data')->nullable();
            $table->string('app', 50);
            $table->unsignedInteger('user_id')->nullable();
            $table->string('ip', 255)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->string('remote_url', 255)->nullable();
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
        $this->schema->dropIfExists(Table::TOKEN);
    }
};
