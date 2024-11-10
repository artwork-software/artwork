<?php

namespace Artwork\Core\FileHandling\Upload\FileHandler;

use Artwork\Core\FileHandling\Upload\UploadFile;
use Artwork\Core\System\Settings\Service\SystemSettingService;
use Artwork\Core\System\Settings\SystemSettingKeys;
use Illuminate\Http\UploadedFile;

class ProjectFileHandler implements UploadFileHandler
{
    use PerformsMimeCheck;
    
    public function __construct(private readonly SystemSettingService $systemSettingService)
    {
    }

    public function handle(UploadedFile $uploadedFile): UploadFile
    {
        if ($this->checkMime($uploadedFile, $this->getAllowedMimeTypes()) === false) {
            throw new \Exception('Invalid mime type');
        }
        return new UploadFile($uploadedFile);
    }

    public function getAllowedMimeTypes(): array
    {
        return $this->systemSettingService->getValueFor(
            SystemSettingKeys::ALLOWED_PROJECT_FILE_MIMETYPES
        )?->value ?? ['*'];
    }
}