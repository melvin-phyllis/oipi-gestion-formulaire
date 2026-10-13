<?php

namespace App\Services\Backup;

use App\Contracts\Backup\ApplicationMaintenanceInterface;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class ArtisanApplicationMaintenance implements ApplicationMaintenanceInterface
{
    public function maintenance_down(): void
    {
        if (Artisan::call('down') !== 0) {
            throw new RuntimeException('Impossible d’activer le mode maintenance.');
        }
    }

    public function maintenance_up(): void
    {
        if (Artisan::call('up') !== 0) {
            throw new RuntimeException('Impossible de désactiver le mode maintenance.');
        }
    }
}
