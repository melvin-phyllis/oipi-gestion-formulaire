<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mis_search_history_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('search_history_code_unique', 40);
            $table->string('search_mission_code_unique', 40)->nullable();
            $table->string('missing_form_code_unique', 40);
            $table->unsignedInteger('tracking_cycle');
            $table->string('search_location_code_unique', 40)->nullable();
            $table->string('search_location_name', 150)->nullable();
            $table->string('responsible_user_code_unique', 40)->nullable();
            $table->string('responsible_name', 205)->nullable();
            $table->string('form_status', 40);
            $table->string('mission_result', 30);
            $table->date('searched_on');
            $table->string('result_summary', 255)->nullable();
            $table->string('recorded_by_user_code_unique', 40)->nullable();
            $table->string('recorded_by_name', 205)->nullable();
            $table->string('source', 30)->default('mission_result');
            $table->timestamps();

            $table->unique('uuid', 'mis_search_history_uuid_unique');
            $table->unique('search_history_code_unique', 'mis_search_history_code_unique');
            $table->unique('search_mission_code_unique', 'mis_search_history_mission_unique');
            $table->index(
                ['missing_form_code_unique', 'searched_on'],
                'mis_search_history_form_date_index',
            );

            $table->foreign('search_mission_code_unique', 'mis_search_history_mission_fk')
                ->references('search_mission_code_unique')
                ->on('mis_search_mission_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('missing_form_code_unique', 'mis_search_history_form_fk')
                ->references('missing_form_code_unique')
                ->on('reg_missing_form_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('search_location_code_unique', 'mis_search_history_location_fk')
                ->references('search_location_code_unique')
                ->on('reg_search_location_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('responsible_user_code_unique', 'mis_search_history_responsible_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('recorded_by_user_code_unique', 'mis_search_history_recorded_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mis_search_history_models');
    }
};
