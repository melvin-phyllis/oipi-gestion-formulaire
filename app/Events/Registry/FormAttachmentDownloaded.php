<?php

namespace App\Events\Registry;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FormAttachmentDownloaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $attachmentUuid,
        public readonly string $missingFormUuid,
        public readonly string $actorUuid,
    ) {}
}
