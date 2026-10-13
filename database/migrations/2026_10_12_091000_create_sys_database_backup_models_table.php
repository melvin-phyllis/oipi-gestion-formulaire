<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_database_backup_models', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('database_backup_code_unique', 40)->unique();
            $table->string('backup_type', 30)->index();
            $table->string('disk', 50);
            $table->string('file_path', 500)->unique();
            $table->string('file_name', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('database_name', 150);
            $table->string('created_by_user_code_unique', 40)->nullable()->index();
            $table->string('creator_name', 220)->nullable();
            $table->string('deleted_by_user_code_unique', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['backup_type', 'created_at'], 'sys_backups_type_created_index');

            $table->foreign('created_by_user_code_unique', 'sys_backups_creator_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('deleted_by_user_code_unique', 'sys_backups_deleter_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_database_backup_models');
    }
};
