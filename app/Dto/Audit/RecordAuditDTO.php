<?php

namespace App\Dto\Audit;

use App\Enums\Audit\AuditAction;
use Carbon\CarbonImmutable;

final readonly class RecordAuditDTO
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public AuditAction $action,
        public string $description,
        public ?string $actorUserCodeUnique,
        public ?string $actorName,
        public ?string $subjectType,
        public ?string $subjectUuid,
        public ?string $subjectCodeUnique,
        public ?string $subjectLabel,
        public ?string $routeName,
        public ?string $httpMethod,
        public ?string $requestPath,
        public ?string $ipAddress,
        public ?string $userAgent,
        public array $metadata,
        public CarbonImmutable $occurredAt,
    ) {}
}
