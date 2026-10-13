<?php

namespace App\Console\Commands;

use App\Services\Mission\MissionReminderService;
use Illuminate\Console\Command;

final class DispatchMissionReminders extends Command
{
    protected $signature = 'risfm:mission-reminders';

    protected $description = 'Dispatch due mission reminder jobs';

    public function handle(MissionReminderService $service): int
    {
        $count = $service->srv_dispatchScheduled();
        $this->info("{$count} relance(s) de mission mise(s) en file d’attente.");

        return self::SUCCESS;
    }
}
