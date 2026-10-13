<?php

namespace App\Dto\Registry;

final readonly class CreateFormAttachmentDTO
{
    public function __construct(
        public string $uuid,
        public string $missingFormCodeUnique,
        public string $disk,
        public string $filePath,
        public string $storedName,
        public string $originalName,
        public string $downloadName,
        public string $mimeType,
        public string $extension,
        public int $sizeBytes,
        public string $sha256,
        public string $uploadedByUserCodeUnique,
        public string $uploaderName,
    ) {}
}
