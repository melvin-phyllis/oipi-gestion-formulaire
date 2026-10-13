<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_title_type_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title_type_code_unique', 40)->unique();
            $table->string('title_type_code', 20)->unique();
            $table->string('title_type_name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['is_active', 'display_order'],
                'reg_title_types_active_order_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_title_type_models');
    }
};
