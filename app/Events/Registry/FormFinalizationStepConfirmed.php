<?php

namespace App\Events\Registry;

use App\Enums\Registry\FinalizationStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FormFinalizationStepConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $missingFormUuid,
        public readonly string $finalizationUuid,
        public readonly FinalizationStep $step,
        public readonly string $actorUuid,
    ) {}
}
