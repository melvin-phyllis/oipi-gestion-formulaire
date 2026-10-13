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
        Schema::create('adm_user_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('user_code_unique', 40)->unique();
            $table->string('role_code_unique', 40);
            $table->string('username', 50)->unique();
            $table->string('last_name', 100);
            $table->string('first_names', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('session_version')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->string('created_by_code_unique', 40)->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role_code_unique');

            $table->foreign('role_code_unique')
                ->references('role_code_unique')
                ->on('adm_role_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::table('adm_user_models', function (Blueprint $table) {
            $table->foreign('created_by_code_unique')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adm_user_models');
    }
};
