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
        Schema::create('reg_direct_found_report_models', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique('reg_direct_found_uuid_unique');
            $table->string('direct_found_report_code_unique', 40)
                ->unique('reg_direct_found_code_unique');
            $table->string('missing_form_code_unique', 40);
            $table->unsignedInteger('tracking_cycle');
            $table->string('finder_type', 20)->index('reg_direct_found_type_index');
            $table->string('finder_user_code_unique', 40)->nullable()
                ->index('reg_direct_found_finder_index');
            $table->string('finder_name', 220);
            $table->string('finder_organization', 255)->nullable();
            $table->string('finder_contact', 150)->nullable();
            $table->string('found_location_code_unique', 40)
                ->index('reg_direct_found_location_index');
            $table->date('found_on')->index('reg_direct_found_date_index');
            $table->text('result_summary');
            $table->string('recorded_by_user_code_unique', 40)
                ->index('reg_direct_found_recorder_index');
            $table->string('recorded_by_name', 220);
            $table->timestamps();

            $table->unique(['missing_form_code_unique', 'tracking_cycle'], 'reg_direct_found_form_cycle_unique');
            $table->foreign('missing_form_code_unique', 'reg_direct_found_form_fk')
                ->references('missing_form_code_unique')->on('reg_missing_form_models')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('finder_user_code_unique', 'reg_direct_found_finder_fk')
                ->references('user_code_unique')->on('adm_user_models')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('found_location_code_unique', 'reg_direct_found_location_fk')
                ->references('search_location_code_unique')->on('reg_search_location_models')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('recorded_by_user_code_unique', 'reg_direct_found_recorder_fk')
                ->references('user_code_unique')->on('adm_user_models')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_direct_found_report_models');
    }
};
