<?php

namespace App\Services\Registry;

use App\Contracts\Administration\UserDirectoryServiceInterface;
use App\Dto\Registry\ImportMissingFormsDTO;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\RegistryImportStatus;
use App\Events\Registry\MissingFormImportProgressUpdated;
use App\Events\Registry\MissingFormsImported;
use App\Jobs\Registry\ProcessMissingFormImport;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormImportModel;
use App\Models\Registry\TitleTypeModel;
use App\Repositories\Interfaces\Registry\MissingFormImportRepositoryInterface;
use App\Repositories\Interfaces\Registry\MissingFormRepositoryInterface;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SplFileObject;
use Throwable;

final class QueuedMissingFormImportService
{
    /** @var array<string, string> */
    private const HEADER_ALIASES = [
        'type_titre' => 'title_type',
        'type_de_titre' => 'title_type',
        'code_type_titre' => 'title_type',
        'title_type' => 'title_type',
        'annee' => 'form_year',
        'annee_titre' => 'form_year',
        'form_year' => 'form_year',
        'numero_formulaire' => 'form_number',
        'numero_du_formulaire' => 'form_number',
        'form_number' => 'form_number',
        'priorite' => 'priority',
        'priority' => 'priority',
    ];

    public function __construct(
        private readonly MissingFormImportRepositoryInterface $imports,
        private readonly MissingFormRepositoryInterface $forms,
        private readonly RegistryReferenceRepositoryInterface $references,
        private readonly UserDirectoryServiceInterface $users,
        private readonly XlsxRowReaderService $xlsx,
    ) {}

    public function srv_queue(
        ImportMissingFormsDTO $data,
        UserModel $actor,
    ): MissingFormImportModel {
        $disk = (string) config('risfm.registry_import.disk', 'local');
        $directory = trim((string) config('risfm.registry_import.directory', 'imports/registry'), '/');
        $extension = Str::lower($data->extension);
        $filePath = $directory.'/'.Str::uuid().'.'.$extension;
        $stream = fopen($data->filePath, 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'import_file' => 'Le fichier temporaire ne peut pas être lu.',
            ]);
        }

        try {
            if (! Storage::disk($disk)->put($filePath, $stream)) {
                throw new RuntimeException('Le fichier d’import ne peut pas être stocké.');
            }
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'import_file' => 'Le fichier d’import ne peut pas être stocké de manière sécurisée.',
            ]);
        } finally {
            fclose($stream);
        }

        try {
            $import = $this->imports->repo_create([
                'disk' => $disk,
                'file_path' => $filePath,
                'original_file_name' => Str::limit(basename($data->originalFileName), 255, ''),
                'size_bytes' => $data->sizeBytes,
                'created_by_user_code_unique' => $actor->user_code_unique,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($filePath);

            throw $exception;
        }

        ProcessMissingFormImport::dispatch($import->missing_form_import_code_unique)
            ->afterCommit();

        return $import;
    }

    public function srv_process(string $importCodeUnique): void
    {
        $import = $this->imports->repo_findByCodeUnique($importCodeUnique);

        if ($import->status === RegistryImportStatus::Completed) {
            return;
        }

        $this->imports->repo_markProcessing($import);
        $this->broadcastProgress($import);
        $actor = $this->users->srv_findByCodeUnique($import->created_by_user_code_unique);
        $disk = Storage::disk($import->disk);
        $sourcePath = $disk->path($import->file_path);
        $normalizedRelativePath = $import->file_path.'.normalized.csv';
        $normalizedPath = $disk->path($normalizedRelativePath);
        $disk->delete($normalizedRelativePath);

        try {
            $this->imports->repo_setTotalRows(
                $import,
                $this->countDataRows($sourcePath),
            );
            $this->broadcastProgress($import);
            [$processedRows, $errors] = $this->validateAndNormalize(
                $sourcePath,
                $normalizedPath,
                $import,
            );

            if ($errors !== []) {
                $this->imports->repo_markFailed($import, $processedRows, $errors);
                $this->broadcastProgress($import);
                $disk->delete([$import->file_path, $normalizedRelativePath]);

                return;
            }

            DB::transaction(function () use ($normalizedPath, $import, $actor, $processedRows): void {
                $this->insertNormalizedRows($normalizedPath, $import, $actor);
                $this->forms->repo_finalizeImportedRegistryNumbers(
                    $import->missing_form_import_code_unique,
                );
                $this->imports->repo_markCompleted($import, $processedRows);
            });

            MissingFormsImported::dispatch($processedRows, $actor->uuid);
            $this->broadcastProgress($import);
            $disk->delete([$import->file_path, $normalizedRelativePath]);
        } catch (ValidationException $exception) {
            $this->imports->repo_markFailed(
                $import,
                $import->processed_rows,
                array_slice($exception->errors()['import_file'] ?? [$exception->getMessage()], 0, 50),
            );
            $this->broadcastProgress($import);
            $disk->delete([$import->file_path, $normalizedRelativePath]);
        } catch (Throwable $exception) {
            $disk->delete($normalizedRelativePath);

            throw $exception;
        }
    }

    public function srv_fail(string $importCodeUnique, string $message): void
    {
        $import = $this->imports->repo_findByCodeUnique($importCodeUnique);

        if ($import->status === RegistryImportStatus::Completed) {
            return;
        }

        $this->imports->repo_markFailed(
            $import,
            $import->processed_rows,
            [$message],
        );
        $this->broadcastProgress($import);
        Storage::disk($import->disk)->delete([
            $import->file_path,
            $import->file_path.'.normalized.csv',
        ]);
    }

    /** @return array{int, list<string>} */
    private function validateAndNormalize(
        string $sourcePath,
        string $normalizedPath,
        MissingFormImportModel $import,
    ): array {
        $normalizedHandle = fopen($normalizedPath, 'wb');

        if ($normalizedHandle === false) {
            throw new RuntimeException('Le fichier de travail de l’import ne peut pas être créé.');
        }

        $columns = null;
        $titleTypes = $this->titleTypeLookup();
        $errors = [];
        $identities = [];
        $batch = [];
        $processedRows = 0;
        $lastBroadcastMilestone = 0;
        $maxRows = max(1, (int) config('risfm.registry_import.max_rows', 100000));
        $chunkSize = max(100, (int) config('risfm.registry_import.chunk_size', 1000));

        try {
            foreach ($this->rowIterator($sourcePath) as $lineNumber => $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                if ($columns === null) {
                    $columns = $this->resolveColumns($row);

                    continue;
                }

                $processedRows++;

                if ($processedRows > $maxRows) {
                    $errors[] = "Le fichier dépasse la limite de {$maxRows} lignes de données.";

                    break;
                }

                $normalized = $this->normalizeRow($row, $columns, $titleTypes, $lineNumber, $errors);

                if ($normalized === null) {
                    continue;
                }

                $identity = $this->identityKey($normalized);

                if (isset($identities[$identity])) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : ce formulaire est déjà présent à la ligne {$identities[$identity]} du fichier.",
                    );

                    continue;
                }

                $identities[$identity] = $lineNumber;
                $normalized['line_number'] = $lineNumber;
                $batch[] = $normalized;

                if (count($batch) >= $chunkSize) {
                    $this->flushValidationBatch($batch, $normalizedHandle, $errors);
                    $batch = [];
                    $this->imports->repo_updateValidationProgress($import, $processedRows);
                    $lastBroadcastMilestone = $this->broadcastProgressAtMilestone(
                        $import,
                        $lastBroadcastMilestone,
                    );
                }
            }

            if ($columns === null) {
                $this->addError($errors, 'Le fichier d’import est vide.');
            }

            if ($batch !== []) {
                $this->flushValidationBatch($batch, $normalizedHandle, $errors);
            }

            if ($processedRows === 0 && $columns !== null) {
                $this->addError($errors, 'Le fichier ne contient aucune ligne de formulaire à importer.');
            }

            $this->imports->repo_updateValidationProgress($import, $processedRows);
            $this->broadcastProgressAtMilestone($import, $lastBroadcastMilestone);
        } catch (ValidationException $exception) {
            foreach ($exception->errors()['import_file'] ?? [$exception->getMessage()] as $message) {
                $this->addError($errors, $message);
            }
        } finally {
            fclose($normalizedHandle);
        }

        return [$processedRows, $errors];
    }

    private function broadcastProgressAtMilestone(
        MissingFormImportModel $import,
        int $lastMilestone,
    ): int {
        $progress = $this->progressPercentage($import);
        $currentMilestone = min(95, intdiv($progress, 5) * 5);

        if ($currentMilestone <= $lastMilestone) {
            return $lastMilestone;
        }

        $this->broadcastProgress($import);

        return $currentMilestone;
    }

    private function broadcastProgress(MissingFormImportModel $import): void
    {
        try {
            MissingFormImportProgressUpdated::dispatch(
                importUuid: $import->uuid,
                status: $import->status->value,
                totalRows: $import->total_rows,
                processedRows: $import->processed_rows,
                importedRows: $import->imported_rows,
                errorCount: $import->error_count,
                progress: $this->progressPercentage($import),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function progressPercentage(MissingFormImportModel $import): int
    {
        if ($import->status === RegistryImportStatus::Completed) {
            return 100;
        }

        if ($import->total_rows === 0) {
            return 0;
        }

        return min(99, (int) floor(($import->processed_rows / $import->total_rows) * 100));
    }

    /**
     * @param  list<array{title_type_code_unique: string, form_year: int, form_number: string, priority: string, line_number: int}>  $batch
     * @param  resource  $normalizedHandle
     * @param  list<string>  $errors
     */
    private function flushValidationBatch(array $batch, $normalizedHandle, array &$errors): void
    {
        $existing = array_fill_keys(
            $this->forms->repo_existingBusinessIdentityKeys($batch),
            true,
        );

        foreach ($batch as $row) {
            if (isset($existing[$this->identityKey($row)])) {
                $this->addError(
                    $errors,
                    "Ligne {$row['line_number']} : ce formulaire existe déjà dans le registre.",
                );

                continue;
            }

            fputcsv($normalizedHandle, [
                $row['title_type_code_unique'],
                $row['form_year'],
                $row['form_number'],
                $row['priority'],
            ], ';', '"', '');
        }
    }

    private function insertNormalizedRows(
        string $normalizedPath,
        MissingFormImportModel $import,
        UserModel $actor,
    ): void {
        $file = new SplFileObject($normalizedPath, 'rb');
        $file->setCsvControl(';', '"', '');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $chunkSize = max(100, (int) config('risfm.registry_import.chunk_size', 1000));
        $batch = [];

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null] || count($row) < 4) {
                continue;
            }

            $batch[] = [
                'title_type_code_unique' => (string) $row[0],
                'form_year' => (int) $row[1],
                'form_number' => (string) $row[2],
                'priority' => (string) $row[3],
            ];

            if (count($batch) >= $chunkSize) {
                $this->forms->repo_bulkCreateImported(
                    $batch,
                    $actor->user_code_unique,
                    $import->missing_form_import_code_unique,
                );
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->forms->repo_bulkCreateImported(
                $batch,
                $actor->user_code_unique,
                $import->missing_form_import_code_unique,
            );
        }
    }

    /** @return Generator<int, array<int, mixed>> */
    private function rowIterator(string $filePath): Generator
    {
        $extension = Str::lower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'], true)) {
            $file = new SplFileObject($filePath, 'rb');
            $file->setCsvControl($this->detectCsvDelimiter($filePath), '"', '');
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);

            foreach ($file as $zeroBasedLine => $row) {
                if (is_array($row) && $row !== [null]) {
                    yield $zeroBasedLine + 1 => array_values($row);
                }
            }

            return;
        }

        if ($extension !== 'xlsx') {
            throw ValidationException::withMessages([
                'import_file' => 'Le fichier ne peut pas être lu. Vérifiez qu’il s’agit d’un fichier CSV ou XLSX valide.',
            ]);
        }

        yield from $this->xlsx->srv_rows($filePath);
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<string, int>
     */
    private function resolveColumns(array $row): array
    {
        $columns = [];

        foreach ($row as $columnIndex => $heading) {
            $canonical = self::HEADER_ALIASES[$this->lookupKey($this->cellString($heading))] ?? null;

            if ($canonical !== null && ! isset($columns[$canonical])) {
                $columns[$canonical] = $columnIndex;
            }
        }

        if (array_diff(['title_type', 'form_year', 'form_number'], array_keys($columns)) !== []) {
            throw ValidationException::withMessages([
                'import_file' => 'Les colonnes obligatoires sont : Type de titre, Année et Numéro du formulaire.',
            ]);
        }

        return $columns;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columns
     * @param  array<string, TitleTypeModel>  $titleTypes
     * @param  list<string>  $errors
     * @return array{title_type_code_unique: string, form_year: int, form_number: string, priority: string}|null
     */
    private function normalizeRow(
        array $row,
        array $columns,
        array $titleTypes,
        int $lineNumber,
        array &$errors,
    ): ?array {
        $titleTypeValue = $this->cellString($row[$columns['title_type']] ?? null);
        $yearValue = $this->cellString($row[$columns['form_year']] ?? null);
        $formNumber = Str::upper($this->cellString($row[$columns['form_number']] ?? null));
        $priorityValue = isset($columns['priority'])
            ? $this->cellString($row[$columns['priority']] ?? null)
            : '';
        $titleType = $titleTypes[$this->lookupKey($titleTypeValue)] ?? null;
        $year = $this->validatedYear($yearValue);
        $priority = $this->priorityFromValue($priorityValue);
        $isValid = true;

        if (! $titleType instanceof TitleTypeModel) {
            $this->addError($errors, "Ligne {$lineNumber} : le type de titre « {$titleTypeValue} » est inconnu ou inactif.");
            $isValid = false;
        }

        if ($year === null) {
            $this->addError($errors, "Ligne {$lineNumber} : l’année doit être comprise entre 1900 et ".now()->year.'.');
            $isValid = false;
        }

        if ($formNumber === '' || mb_strlen($formNumber) > 60) {
            $this->addError($errors, "Ligne {$lineNumber} : le numéro du formulaire est obligatoire et limité à 60 caractères.");
            $isValid = false;
        }

        if ($priority === null) {
            $this->addError($errors, "Ligne {$lineNumber} : la priorité « {$priorityValue} » est invalide.");
            $isValid = false;
        }

        if (! $isValid) {
            return null;
        }

        return [
            'title_type_code_unique' => $titleType->title_type_code_unique,
            'form_year' => $year,
            'form_number' => $formNumber,
            'priority' => $priority->value,
        ];
    }

    /** @return array<string, TitleTypeModel> */
    private function titleTypeLookup(): array
    {
        $lookup = [];

        foreach ($this->references->repo_availableTitleTypes() as $titleType) {
            $lookup[$this->lookupKey($titleType->title_type_code)] = $titleType;
            $lookup[$this->lookupKey($titleType->title_type_name)] = $titleType;
        }

        return $lookup;
    }

    private function validatedYear(string $value): ?int
    {
        if (! preg_match('/^\d{4}$/', $value)) {
            return null;
        }

        $year = (int) $value;

        return $year >= 1900 && $year <= now()->year ? $year : null;
    }

    private function priorityFromValue(string $value): ?FormPriority
    {
        if ($value === '') {
            return FormPriority::Normal;
        }

        return match ($this->lookupKey($value)) {
            'low', 'basse' => FormPriority::Low,
            'normal', 'normale' => FormPriority::Normal,
            'high', 'haute' => FormPriority::High,
            'urgent', 'urgente' => FormPriority::Urgent,
            default => null,
        };
    }

    /** @param array<int, mixed> $row */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($this->cellString($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cellString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function lookupKey(string $value): string
    {
        $ascii = Str::lower(Str::ascii(trim($value)));

        return trim(preg_replace('/[^a-z0-9]+/', '_', $ascii) ?? '', '_');
    }

    /** @param array{title_type_code_unique: string, form_year: int, form_number: string} $row */
    private function identityKey(array $row): string
    {
        return implode('|', [
            $row['form_year'],
            $row['title_type_code_unique'],
            $row['form_number'],
        ]);
    }

    /** @param list<string> $errors */
    private function addError(array &$errors, string $message): void
    {
        if (count($errors) < 50) {
            $errors[] = $message;
        }
    }

    private function detectCsvDelimiter(string $filePath): string
    {
        $sample = (string) file_get_contents($filePath, false, null, 0, 4096);

        return substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
    }

    private function countDataRows(string $filePath): int
    {
        $nonEmptyRows = 0;
        $maxRows = max(1, (int) config('risfm.registry_import.max_rows', 100000));

        foreach ($this->rowIterator($filePath) as $row) {
            if (! $this->rowIsEmpty($row)) {
                $nonEmptyRows++;
                if ($nonEmptyRows > $maxRows + 1) {
                    throw ValidationException::withMessages([
                        'import_file' => "Le fichier dépasse la limite de {$maxRows} lignes de données.",
                    ]);
                }
            }
        }

        return max(0, $nonEmptyRows - 1);
    }
}
