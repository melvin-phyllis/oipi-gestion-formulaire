<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_form_finalization_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('form_finalization_code_unique', 40);
            $table->string('missing_form_code_unique', 40);
            $table->unsignedInteger('tracking_cycle');
            $table->string('step', 30);
            $table->string('performed_by_user_code_unique', 40)->nullable();
            $table->string('performed_by_name', 205)->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'reg_form_finalizations_uuid_unique');
            $table->unique('form_finalization_code_unique', 'reg_form_finalizations_code_unique');
            $table->unique(
                ['missing_form_code_unique', 'tracking_cycle', 'step'],
                'reg_form_finalizations_cycle_step_unique',
            );
            $table->index(['step', 'performed_at'], 'reg_form_finalizations_step_date_index');

            $table->foreign('missing_form_code_unique', 'reg_form_finalizations_form_fk')
                ->references('missing_form_code_unique')
                ->on('reg_missing_form_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('performed_by_user_code_unique', 'reg_form_finalizations_actor_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_form_finalization_models');
    }
};
