<?php

namespace Artwork\Modules\System\FileHandling\Service;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Modules\GeneralSettings\Dto\FileHandlingDto;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\System\FileHandling\RetrievesSettingsForFileType;

class FileHandlingFrontendService
{
    use RetrievesSettingsForFileType;
    
    public function __construct(private readonly GeneralSettingsService $generalSettingsService)
    {
    }

    public function createFileHandingDto(ArtworkFileTypes $fileType): FileHandlingDto
    {
        $data = $this->retrieveSettingsForFileType($fileType);
        
        return new FileHandlingDto(
            fileTypes: array_map(static fn($type) => ['name' => $type], $data['mime_types']),
            name: $fileType->value,
            fileSize: $data['file_size']
        );
    }
    
}
