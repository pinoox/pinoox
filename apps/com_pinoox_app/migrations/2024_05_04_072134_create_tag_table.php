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

namespace App\com_pinoox_shop\migrations;

use App\com_pinoox_shop\Model\Table;
use Illuminate\Database\Schema\Blueprint;
use Pinoox\Component\Migration\MigrationBase;

return new class extends MigrationBase {
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->schema->create('com_pinoox_app_tag', function (Blueprint $table) {
            $table->increments('tag_id');
            $table->string('tag_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->schema->dropIfExists('com_pinoox_app_tag');
    }
};
