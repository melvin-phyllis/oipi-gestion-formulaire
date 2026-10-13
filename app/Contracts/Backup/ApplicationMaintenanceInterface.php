<?php

namespace App\Contracts\Backup;

interface ApplicationMaintenanceInterface
{
    public function maintenance_down(): void;

    public function maintenance_up(): void;
}
