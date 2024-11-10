<?php

namespace Artwork\Core\FileHandling\Upload;

use Illuminate\Http\UploadedFile;

class UploadFile
{
    public function __construct(
        private readonly UploadedFile $uploadedFile,
    )
    {
    }
    
    public function getMimeType(): string
    {
        return $this->uploadedFile->getClientMimeType();
    }
    
    public function getOriginalName(): string
    {
        return $this->uploadedFile->getClientOriginalName();
    }
}