<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_form_reopening_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('form_reopening_code_unique', 40);
            $table->string('missing_form_code_unique', 40);
            $table->unsignedInteger('previous_tracking_cycle');
            $table->unsignedInteger('new_tracking_cycle');
            $table->string('previous_status', 40);
            $table->string('reason', 500);
            $table->string('reopened_by_user_code_unique', 40)->nullable();
            $table->string('reopened_by_name', 205)->nullable();
            $table->timestamp('reopened_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'reg_form_reopenings_uuid_unique');
            $table->unique('form_reopening_code_unique', 'reg_form_reopenings_code_unique');
            $table->unique(
                ['missing_form_code_unique', 'previous_tracking_cycle'],
                'reg_form_reopenings_cycle_unique',
            );
            $table->index('reopened_at', 'reg_form_reopenings_date_index');

            $table->foreign('missing_form_code_unique', 'reg_form_reopenings_form_fk')
                ->references('missing_form_code_unique')
                ->on('reg_missing_form_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('reopened_by_user_code_unique', 'reg_form_reopenings_actor_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_form_reopening_models');
    }
};
