<?php

namespace App\Dto\Audit;

final readonly class AuditRequestContextDTO
{
    public function __construct(
        public ?string $routeName = null,
        public ?string $httpMethod = null,
        public ?string $requestPath = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
