<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adm_role_permission_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('role_permission_code_unique', 40)->unique();
            $table->string('role_code_unique', 40);
            $table->string('permission_code_unique', 40);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['role_code_unique', 'permission_code_unique'],
                'adm_role_permission_unique'
            );

            $table->foreign('role_code_unique')
                ->references('role_code_unique')
                ->on('adm_role_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('permission_code_unique')
                ->references('permission_code_unique')
                ->on('adm_permission_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adm_role_permission_models');
    }
};
