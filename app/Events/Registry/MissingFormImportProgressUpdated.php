<?php

namespace App\Events\Registry;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MissingFormImportProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $importUuid,
        public readonly string $status,
        public readonly int $totalRows,
        public readonly int $processedRows,
        public readonly int $importedRows,
        public readonly int $errorCount,
        public readonly int $progress,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("registry-imports.{$this->importUuid}");
    }

    public function broadcastAs(): string
    {
        return 'registry.import.progress.updated';
    }

    /** @return array<string, int|string> */
    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->importUuid,
            'status' => $this->status,
            'total_rows' => $this->totalRows,
            'processed_rows' => $this->processedRows,
            'imported_rows' => $this->importedRows,
            'error_count' => $this->errorCount,
            'progress' => $this->progress,
        ];
    }
}
