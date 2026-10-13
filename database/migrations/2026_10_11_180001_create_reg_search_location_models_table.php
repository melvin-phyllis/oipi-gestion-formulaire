<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_search_location_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('search_location_code_unique', 40)->unique();
            $table->string('location_code', 60)->unique();
            $table->string('location_name', 150)->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['is_active', 'display_order'],
                'reg_search_locations_active_order_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_search_location_models');
    }
};
