<?php

namespace App\Dto\Registry;

final readonly class UploadFormAttachmentDTO
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $clientExtension,
        public ?string $clientMimeType,
        public int $sizeBytes,
    ) {}
}
