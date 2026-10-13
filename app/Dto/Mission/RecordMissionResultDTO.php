<?php

namespace App\Dto\Mission;

use App\Enums\Mission\MissionResult;
use Carbon\CarbonImmutable;

final readonly class RecordMissionResultDTO
{
    public function __construct(
        public MissionResult $result,
        public string $resultSummary,
        public CarbonImmutable $searchedOn,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            MissionResult::from($validated['result']),
            $validated['result_summary'],
            CarbonImmutable::parse($validated['searched_on']),
        );
    }
}
