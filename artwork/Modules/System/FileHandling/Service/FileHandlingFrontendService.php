<?php

namespace Artwork\Modules\System\FileHandling\Service;

use Artwork\Core\Dto\KeyValueDto;
use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Modules\GeneralSettings\Dto\FileHandlingDto;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\System\FileHandling\MimeTypeList;

class FileHandlingFrontendService
{
    public function __construct(private readonly GeneralSettingsService $generalSettingsService)
    {
    }

    public function createFileHandingDto(ArtworkFileTypes $fileType): FileHandlingDto
    {
        $mimeTypes = match ($fileType) {
            ArtworkFileTypes::PROJECT => $this->generalSettingsService->getAllowedProjectFileMimeTypes(),
            ArtworkFileTypes::ROOM => $this->generalSettingsService->getAllowedRoomFileMimeTypes(),
            ArtworkFileTypes::BRANDING => $this->generalSettingsService->getAllowedBrandingFileMimeTypes(),
        };
        
        return new FileHandlingDto(
            fileTypes: array_filter(MimeTypeList::IMAGE_MIME_TYPES, fn($key) => in_array($key, $mimeTypes)),
            name: $fileType->value
        );
    }
    
    public function createMimeTypeDto(): KeyValueDto
    {
        return new KeyValueDto(
            key: 'mime_types',
            value: MimeTypeList::MIME_TYPES
        );
    }
}