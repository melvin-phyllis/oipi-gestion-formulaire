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
        Schema::create('org_service_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('service_code_unique', 40)->unique();
            $table->string('directorate_code_unique', 40);
            $table->string('service_code', 40)->unique();
            $table->string('abbreviation', 30)->nullable();
            $table->string('service_name', 200);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['directorate_code_unique', 'service_name'],
                'org_services_directorate_name_unique',
            );
            $table->index(
                ['is_active', 'display_order'],
                'org_services_active_order_index',
            );

            $table->foreign('directorate_code_unique')
                ->references('directorate_code_unique')
                ->on('org_directorate_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_service_models');
    }
};
