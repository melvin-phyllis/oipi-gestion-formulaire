<?php

namespace App\Http\Controllers\Backup;

use App\Dto\Backup\BackupFiltersDTO;
use App\Dto\Backup\RestoreBackupDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\BackupFiltersFormRequest;
use App\Http\Requests\Backup\ManageDatabaseBackupFormRequest;
use App\Http\Requests\Backup\RestoreDatabaseBackupFormRequest;
use App\Models\Administration\UserModel;
use App\Models\Backup\DatabaseBackupModel;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BackupController extends Controller
{
    public function __construct(private readonly DatabaseBackupService $backups) {}

    public function ctrl_index(): View
    {
        return view('backups.index');
    }

    public function ctrl_data(BackupFiltersFormRequest $request): JsonResponse
    {
        $backups = $this->backups->srv_list(
            BackupFiltersDTO::fromValidated($request->validated()),
        );

        return response()->json([
            'data' => $backups->getCollection()
                ->map(fn (DatabaseBackupModel $backup): array => $this->serialize($backup))
                ->values(),
            'meta' => [
                'current_page' => $backups->currentPage(),
                'last_page' => $backups->lastPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
            ],
        ]);
    }

    public function ctrl_store(ManageDatabaseBackupFormRequest $request): JsonResponse
    {
        $backup = $this->backups->srv_createManual($this->actor($request->user()));

        return response()->json([
            'message' => 'La sauvegarde de la base de données a été créée.',
            'data' => $this->serialize($backup),
        ], 201);
    }

    public function ctrl_download(
        ManageDatabaseBackupFormRequest $request,
        DatabaseBackupModel $backup,
    ): StreamedResponse {
        return $this->backups->srv_download($backup, $this->actor($request->user()));
    }

    public function ctrl_destroy(
        ManageDatabaseBackupFormRequest $request,
        DatabaseBackupModel $backup,
    ): JsonResponse {
        $this->backups->srv_delete($backup, $this->actor($request->user()));

        return response()->json([
            'message' => 'La sauvegarde a été supprimée.',
        ]);
    }

    public function ctrl_restore(
        RestoreDatabaseBackupFormRequest $request,
        DatabaseBackupModel $backup,
    ): JsonResponse {
        $safetyBackup = $this->backups->srv_restore(
            $backup,
            RestoreBackupDTO::fromValidated($request->validated()),
            $this->actor($request->user()),
        );

        return response()->json([
            'message' => 'La base de données a été restaurée. Une sauvegarde de sécurité préalable a été conservée.',
            'safety_backup' => $this->serialize($safetyBackup),
        ]);
    }

    /** @return array<string, mixed> */
    private function serialize(DatabaseBackupModel $backup): array
    {
        return [
            'uuid' => $backup->uuid,
            'code' => $backup->database_backup_code_unique,
            'type' => $backup->backup_type->value,
            'file_name' => $backup->file_name,
            'size_bytes' => $backup->size_bytes,
            'creator_name' => $backup->creator_name,
            'created_at' => $backup->created_at?->toIso8601String(),
        ];
    }

    private function actor(mixed $user): UserModel
    {
        abort_unless($user instanceof UserModel, 403);

        return $user;
    }
}
