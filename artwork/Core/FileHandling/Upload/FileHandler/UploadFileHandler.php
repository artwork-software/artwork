<?php

namespace Artwork\Core\FileHandling\Upload\FileHandler;

use Artwork\Core\FileHandling\Upload\UploadFile;
use Illuminate\Http\UploadedFile;

interface UploadFileHandler
{
    public function getAllowedMimeTypes(): array;
    public function handle(UploadedFile $uploadedFile): UploadFile;
}