<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mis_mission_reminder_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('mission_reminder_code_unique', 40);
            $table->string('search_mission_code_unique', 40);
            $table->string('recipient_user_code_unique', 40)->nullable();
            $table->string('type', 30);
            $table->date('reminder_date');
            $table->smallInteger('days_delta');
            $table->uuid('notification_id')->nullable();
            $table->timestamp('database_sent_at')->nullable();
            $table->timestamp('email_attempted_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_error', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid', 'mis_mission_reminders_uuid_unique');
            $table->unique('mission_reminder_code_unique', 'mis_mission_reminders_code_unique');
            $table->unique(
                ['search_mission_code_unique', 'recipient_user_code_unique', 'type', 'reminder_date'],
                'mis_mission_reminders_dispatch_unique',
            );
            $table->index(
                ['type', 'reminder_date'],
                'mis_mission_reminders_type_date_index',
            );

            $table->foreign('search_mission_code_unique', 'mis_mission_reminders_mission_fk')
                ->references('search_mission_code_unique')
                ->on('mis_search_mission_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('recipient_user_code_unique', 'mis_mission_reminders_recipient_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('notification_id', 'mis_mission_reminders_notification_fk')
                ->references('id')
                ->on('notifications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mis_mission_reminder_models');
    }
};
