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
        Schema::table('adm_user_models', function (Blueprint $table) {
            $table->string('service_code_unique', 40)
                ->nullable()
                ->after('role_code_unique')
                ->index();

            $table->foreign('service_code_unique')
                ->references('service_code_unique')
                ->on('org_service_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adm_user_models', function (Blueprint $table) {
            $table->dropForeign(['service_code_unique']);
            $table->dropColumn('service_code_unique');
        });
    }
};
