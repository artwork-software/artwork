<?php

namespace Artwork\Core\FileHandling\Upload\FileHandler;


use Illuminate\Http\UploadedFile;

trait PerformsMimeCheck
{
    public function checkMime(UploadedFile $uploadFile): bool
    {
        $allowedMimeType = $this->getAllowedMimeTypes();
        if (empty($allowedMimeType)) {
            return false;
        }

        if ($allowedMimeType[0] === '*') {
            return true;
        }

        return in_array($uploadFile->getMimeType(), $allowedMimeType, true);
    }
}