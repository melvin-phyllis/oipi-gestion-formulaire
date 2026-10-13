<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aud_audit_log_models', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('audit_log_code_unique', 40)->unique();
            $table->string('category', 40)->index();
            $table->string('action_code', 100)->index();
            $table->string('actor_user_code_unique', 40)->nullable()->index();
            $table->string('actor_name', 220)->nullable();
            $table->string('subject_type', 60)->nullable()->index();
            $table->uuid('subject_uuid')->nullable()->index();
            $table->string('subject_code_unique', 40)->nullable()->index();
            $table->string('subject_label', 255)->nullable();
            $table->string('description', 1000);
            $table->string('route_name', 150)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('request_path', 500)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 1000)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['category', 'occurred_at'], 'aud_logs_category_occurred_index');
            $table->index(['actor_user_code_unique', 'occurred_at'], 'aud_logs_actor_occurred_index');

            $table->foreign('actor_user_code_unique', 'aud_logs_actor_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aud_audit_log_models');
    }
};
