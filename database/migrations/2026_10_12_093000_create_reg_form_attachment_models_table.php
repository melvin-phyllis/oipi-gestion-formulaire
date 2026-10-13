<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_form_attachment_models', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('form_attachment_code_unique', 40)->unique();
            $table->string('missing_form_code_unique', 40);
            $table->string('disk', 50);
            $table->string('file_path', 500)->unique();
            $table->string('stored_name', 255);
            $table->string('original_name', 255);
            $table->string('download_name', 255);
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('uploaded_by_user_code_unique', 40)->nullable();
            $table->string('uploader_name', 255)->nullable();
            $table->string('deleted_by_user_code_unique', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['missing_form_code_unique', 'created_at'],
                'reg_form_attachments_form_created_index',
            );
            $table->index('uploaded_by_user_code_unique', 'reg_form_attachments_uploader_index');

            $table->foreign('missing_form_code_unique', 'reg_form_attachments_form_fk')
                ->references('missing_form_code_unique')
                ->on('reg_missing_form_models')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('uploaded_by_user_code_unique', 'reg_form_attachments_uploader_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('deleted_by_user_code_unique', 'reg_form_attachments_deleted_by_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_form_attachment_models');
    }
};
