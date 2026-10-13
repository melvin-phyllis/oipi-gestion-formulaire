<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mis_found_report_review_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('found_report_review_code_unique', 40);
            $table->string('search_mission_code_unique', 40);
            $table->string('missing_form_code_unique', 40);
            $table->unsignedInteger('tracking_cycle');
            $table->string('decision', 20);
            $table->string('rejection_reason', 500)->nullable();
            $table->string('reviewed_by_user_code_unique', 40)->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'mis_found_reviews_uuid_unique');
            $table->unique('found_report_review_code_unique', 'mis_found_reviews_code_unique');
            $table->unique('search_mission_code_unique', 'mis_found_reviews_mission_unique');
            $table->index(
                ['missing_form_code_unique', 'tracking_cycle', 'decision'],
                'mis_found_reviews_form_cycle_decision_index',
            );

            $table->foreign('search_mission_code_unique', 'mis_found_reviews_mission_fk')
                ->references('search_mission_code_unique')
                ->on('mis_search_mission_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('missing_form_code_unique', 'mis_found_reviews_form_fk')
                ->references('missing_form_code_unique')
                ->on('reg_missing_form_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('reviewed_by_user_code_unique', 'mis_found_reviews_reviewer_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mis_found_report_review_models');
    }
};
