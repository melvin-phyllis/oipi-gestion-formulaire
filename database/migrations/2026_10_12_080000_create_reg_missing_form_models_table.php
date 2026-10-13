<?php

use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_missing_form_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('missing_form_code_unique', 40)->unique();
            $table->string('registry_number', 50)->unique();
            $table->string('title_type_code_unique', 40);
            $table->unsignedSmallInteger('form_year');
            $table->string('form_number', 60);
            $table->date('deposit_date')->nullable();
            $table->string('applicant_name', 200)->nullable();
            $table->string('representative_name', 200)->nullable();
            $table->string('status', 40)->default(FormStatus::Missing->value);
            $table->string('status_before_archive', 40)->nullable();
            $table->string('priority', 20)->default(FormPriority::Normal->value);
            $table->unsignedInteger('tracking_cycle')->default(1);
            $table->string('last_search_location_code_unique', 40)->nullable();
            $table->string('last_responsible_user_code_unique', 40)->nullable();
            $table->date('search_due_date')->nullable();
            $table->date('last_search_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('result_summary', 255)->nullable();
            $table->string('archive_reason', 500)->nullable();
            $table->string('archived_by_user_code_unique', 40)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('created_by_user_code_unique', 40)->nullable();
            $table->string('updated_by_user_code_unique', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['form_year', 'title_type_code_unique', 'form_number'],
                'reg_missing_forms_business_unique',
            );
            $table->index(['status', 'priority'], 'reg_missing_forms_status_priority_index');
            $table->index('last_search_date', 'reg_missing_forms_last_search_index');
            $table->index('search_due_date', 'reg_missing_forms_due_date_index');

            $table->foreign('title_type_code_unique', 'reg_missing_forms_title_type_fk')
                ->references('title_type_code_unique')
                ->on('reg_title_type_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('last_search_location_code_unique', 'reg_missing_forms_location_fk')
                ->references('search_location_code_unique')
                ->on('reg_search_location_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('last_responsible_user_code_unique', 'reg_missing_forms_responsible_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('archived_by_user_code_unique', 'reg_missing_forms_archived_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('created_by_user_code_unique', 'reg_missing_forms_created_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('updated_by_user_code_unique', 'reg_missing_forms_updated_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_missing_form_models');
    }
};
