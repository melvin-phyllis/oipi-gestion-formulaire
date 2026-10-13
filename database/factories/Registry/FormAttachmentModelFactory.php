<?php

namespace Database\Factories\Registry;

use App\Models\Administration\UserModel;
use App\Models\Registry\FormAttachmentModel;
use App\Models\Registry\MissingFormModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FormAttachmentModel> */
final class FormAttachmentModelFactory extends Factory
{
    public function definition(): array
    {
        $uuid = (string) Str::uuid();
        $content = '%PDF-1.4 test attachment';

        return [
            'uuid' => $uuid,
            'missing_form_code_unique' => fn (): string => MissingFormModel::factory()
                ->create()
                ->missing_form_code_unique,
            'disk' => 'local',
            'file_path' => "attachments/forms/test/{$uuid}.pdf",
            'stored_name' => "{$uuid}.pdf",
            'original_name' => 'document.pdf',
            'download_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => strlen($content),
            'sha256' => hash('sha256', $content),
            'uploaded_by_user_code_unique' => fn (): string => UserModel::factory()
                ->create()
                ->user_code_unique,
            'uploader_name' => 'Utilisateur Test',
            'deleted_by_user_code_unique' => null,
        ];
    }
}
