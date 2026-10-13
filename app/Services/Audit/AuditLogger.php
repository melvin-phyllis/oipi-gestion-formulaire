<?php

namespace App\Services\Audit;

use App\Dto\Audit\RecordAuditDTO;
use App\Enums\Audit\AuditAction;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use App\Repositories\Interfaces\Audit\AuditRepositoryInterface;
use BackedEnum;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'authorization',
        'cookie',
    ];

    public function __construct(private readonly AuditRepositoryInterface $audit) {}

    /** @param array<string, mixed> $metadata */
    public function srv_record(
        AuditAction $action,
        string $description,
        ?string $actorUuid = null,
        ?string $subjectType = null,
        ?string $subjectUuid = null,
        array $metadata = [],
        ?UserModel $actor = null,
    ): AuditLogModel {
        $actor ??= $actorUuid ? $this->audit->repo_findUserByUuid($actorUuid) : null;
        $subject = $subjectType && $subjectUuid
            ? $this->audit->repo_resolveSubject($subjectType, $subjectUuid)
            : ['codeUnique' => null, 'label' => null];
        $request = $this->currentRequest();

        return $this->audit->repo_create(new RecordAuditDTO(
            action: $action,
            description: Str::limit($description, 1000, ''),
            actorUserCodeUnique: $actor?->user_code_unique,
            actorName: $actor ? trim("{$actor->last_name} {$actor->first_names}") : null,
            subjectType: $subjectType,
            subjectUuid: $subjectUuid,
            subjectCodeUnique: $subject['codeUnique'],
            subjectLabel: $subject['label'],
            routeName: $request?->route()?->getName(),
            httpMethod: $request?->method(),
            requestPath: $request ? Str::limit('/'.$request->path(), 500, '') : null,
            ipAddress: $request?->ip(),
            userAgent: $request ? Str::limit((string) $request->userAgent(), 1000, '') : null,
            metadata: $this->sanitizeMetadata($metadata),
            occurredAt: CarbonImmutable::now(),
        ));
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = Str::lower((string) $key);
            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            return $this->sanitizeMetadata($value);
        }

        if (is_string($value)) {
            return Str::limit($value, 2000, '');
        }

        return is_scalar($value) || $value === null ? $value : get_debug_type($value);
    }
}
