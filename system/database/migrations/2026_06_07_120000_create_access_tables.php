<?php

namespace Pinoox\Database\migrations;

use Illuminate\Database\Schema\Blueprint;
use Pinoox\Component\Migration\MigrationBase;
use Pinoox\System\Model\Table;

return new class extends MigrationBase {
    public function up(): void
    {
        $this->schema->disableForeignKeyConstraints();

        $this->schema->create($this->table(Table::ROLE, 'pincore'), function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('app', 255)->nullable();
            $table->string('role_key', 100);
            $table->string('name', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['app', 'role_key']);
        });

        $this->schema->create($this->table(Table::PERMISSION, 'pincore'), function (Blueprint $table) {
            $table->increments('permission_id');
            $table->string('app', 255)->nullable();
            $table->string('permission_key', 150);
            $table->string('name', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['app', 'permission_key']);
        });

        $this->schema->create($this->table(Table::ROLE_PERMISSION, 'pincore'), function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('permission_id');

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('role_id')->on($this->table(Table::ROLE, 'pincore'))->cascadeOnDelete();
            $table->foreign('permission_id')->references('permission_id')->on($this->table(Table::PERMISSION, 'pincore'))->cascadeOnDelete();
        });

        $this->schema->create($this->table(Table::USER_ROLE, 'pincore'), function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('user_id')->on($this->table(Table::USER, 'pincore'))->cascadeOnDelete();
            $table->foreign('role_id')->references('role_id')->on($this->table(Table::ROLE, 'pincore'))->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists($this->table(Table::USER_ROLE, 'pincore'));
        $this->schema->dropIfExists($this->table(Table::ROLE_PERMISSION, 'pincore'));
        $this->schema->dropIfExists($this->table(Table::PERMISSION, 'pincore'));
        $this->schema->dropIfExists($this->table(Table::ROLE, 'pincore'));
    }
};
