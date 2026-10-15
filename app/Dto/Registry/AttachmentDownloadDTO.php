<?php

namespace App\Dto\Registry;

final readonly class AttachmentDownloadDTO
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $filename,
        public string $mimeType,
    ) {}
}
