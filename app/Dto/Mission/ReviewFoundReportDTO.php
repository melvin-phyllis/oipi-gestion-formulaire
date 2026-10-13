<?php

namespace App\Dto\Mission;

use App\Enums\Mission\FoundReportDecision;

final readonly class ReviewFoundReportDTO
{
    public function __construct(
        public FoundReportDecision $decision,
        public ?string $rejectionReason,
    ) {}

    public static function confirmed(): self
    {
        return new self(FoundReportDecision::Confirmed, null);
    }

    /** @param array<string, mixed> $validated */
    public static function rejected(array $validated): self
    {
        return new self(
            FoundReportDecision::Rejected,
            $validated['rejection_reason'],
        );
    }
}
