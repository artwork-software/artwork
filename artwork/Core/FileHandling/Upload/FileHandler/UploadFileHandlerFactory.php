<?php

namespace Artwork\Core\FileHandling\Upload\FileHandler;

class UploadFileHandlerFactory
{
    public function createFileHandler(string $type): UploadFileHandler
    {
        return match ($type) {
            'PROJECT' => new ProjectFileHandler(),
            'ROOM' => new RoomFileHandler(),
            'BRANDING' => new BrandingFileHandler(),
            default => throw new \InvalidArgumentException('Invalid file type'),
        };
    }
}