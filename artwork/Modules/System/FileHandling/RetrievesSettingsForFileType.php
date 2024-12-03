<?php

namespace Artwork\Modules\System\FileHandling;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;

trait RetrievesSettingsForFileType
{
    protected function retrieveSettingsForFileType(ArtworkFileTypes $type): array
    {
        return match ($type) {
            ArtworkFileTypes::PROJECT => $this->generalSettingsService->getAllowedProjectFileMimeTypes(),
            ArtworkFileTypes::ROOM => $this->generalSettingsService->getAllowedRoomFileMimeTypes(),
            ArtworkFileTypes::BRANDING => $this->generalSettingsService->getAllowedBrandingFileMimeTypes(),
        };
    }
}