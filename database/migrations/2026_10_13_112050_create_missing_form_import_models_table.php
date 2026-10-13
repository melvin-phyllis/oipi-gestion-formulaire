<?php

use App\Enums\Registry\RegistryImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_missing_form_import_models', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique('reg_form_imports_uuid_unique');
            $table->string('missing_form_import_code_unique', 40)->unique('reg_form_imports_code_unique');
            $table->string('status', 30)->default(RegistryImportStatus::Pending->value)->index('reg_form_imports_status_index');
            $table->string('disk', 50);
            $table->string('file_path', 500)->unique('reg_form_imports_file_path_unique');
            $table->string('original_file_name', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->string('created_by_user_code_unique', 40)->nullable()->index('reg_form_imports_creator_index');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by_user_code_unique', 'reg_form_imports_creator_fk')
                ->references('user_code_unique')
                ->on('adm_user_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::table('reg_missing_form_models', function (Blueprint $table): void {
            $table->string('source_import_code_unique', 40)->nullable()->after('updated_by_user_code_unique')->index('reg_missing_forms_source_import_index');
            $table->foreign('source_import_code_unique', 'reg_missing_forms_source_import_fk')
                ->references('missing_form_import_code_unique')
                ->on('reg_missing_form_import_models')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reg_missing_form_models', function (Blueprint $table): void {
            $table->dropForeign('reg_missing_forms_source_import_fk');
            $table->dropColumn('source_import_code_unique');
        });

        Schema::dropIfExists('reg_missing_form_import_models');
    }
};
