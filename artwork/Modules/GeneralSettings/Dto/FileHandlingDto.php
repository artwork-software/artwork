<?php

namespace Artwork\Modules\GeneralSettings\Dto;

class FileHandlingDto
{
    public function __construct(
        public readonly array $fileTypes,
        public readonly string $name,
        public readonly int $fileSize,
    ) {
    }
}
