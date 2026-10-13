<?php

namespace App\Jobs\Registry;

use App\Services\Registry\QueuedMissingFormImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessMissingFormImport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public int $uniqueFor = 7200;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $importCodeUnique,
    ) {
        $this->onQueue('imports');
    }

    public function handle(QueuedMissingFormImportService $service): void
    {
        $service->srv_process($this->importCodeUnique);
    }

    public function failed(Throwable $exception): void
    {
        app(QueuedMissingFormImportService::class)->srv_fail(
            $this->importCodeUnique,
            'Le traitement technique de l’import a échoué. Relancez l’import ou contactez l’administrateur.',
        );
    }

    public function uniqueId(): string
    {
        return $this->importCodeUnique;
    }
}
