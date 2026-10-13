<?php

use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mis_search_mission_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('search_mission_code_unique', 40);
            $table->string('parent_mission_code_unique', 40)->nullable();
            $table->string('missing_form_code_unique', 40);
            $table->unsignedInteger('tracking_cycle')->default(1);
            $table->string('search_location_code_unique', 40);
            $table->string('responsible_user_code_unique', 40);
            $table->string('assigned_by_user_code_unique', 40)->nullable();
            $table->string('closed_by_user_code_unique', 40)->nullable();
            $table->string('status', 30)->default(MissionStatus::Assigned->value);
            $table->string('result', 30)->nullable();
            $table->string('result_summary', 255)->nullable();
            $table->string('priority', 20)->default(FormPriority::Normal->value);
            $table->timestamp('assigned_at');
            $table->date('due_date')->nullable();
            $table->date('searched_on')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->char('active_assignment_key', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'mis_search_missions_uuid_unique');
            $table->unique('search_mission_code_unique', 'mis_search_missions_code_unique');
            $table->unique('active_assignment_key', 'mis_search_missions_active_key_unique');
            $table->index(
                ['missing_form_code_unique', 'tracking_cycle', 'status'],
                'mis_search_missions_form_cycle_status_index',
            );
            $table->index(
                ['responsible_user_code_unique', 'status', 'due_date'],
                'mis_search_missions_responsible_status_due_index',
            );

            $table->foreign('parent_mission_code_unique', 'mis_search_missions_parent_fk')
                ->references('search_mission_code_unique')
                ->on('mis_search_mission_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('missing_form_code_unique', 'mis_search_missions_form_fk')
                ->references('missing_form_code_unique')
                ->on('reg_missing_form_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('search_location_code_unique', 'mis_search_missions_location_fk')
                ->references('search_location_code_unique')
                ->on('reg_search_location_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('responsible_user_code_unique', 'mis_search_missions_responsible_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('assigned_by_user_code_unique', 'mis_search_missions_assigned_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('closed_by_user_code_unique', 'mis_search_missions_closed_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mis_search_mission_models');
    }
};
