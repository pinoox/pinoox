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

namespace App\com_pinoox_manager\migrations;

use Illuminate\Database\Schema\Blueprint;
use Pinoox\Component\Migration\MigrationBase;

return new class extends MigrationBase
{
	/**
	 * Run the migrations.
	 */
    public function up()
    {
        $this->schema->create('com_pinoox_manager_notification', function (Blueprint $table) {
            $table->increments('ntf_id');
            $table->string('app', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('message', 1000)->nullable();
            $table->string('action_key', 255)->nullable();
            $table->json('action_data')->nullable();
            $table->dateTime('push_date')->nullable();
            $table->enum('status', ['pending', 'send', 'seen', 'hide']);
            $table->timestamps();
        });
    }

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		$this->schema->dropIfExists('com_pinoox_manager_notification');
	}
};
