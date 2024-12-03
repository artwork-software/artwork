<?php

namespace Artwork\Core\FileHandling\Upload;

use Artwork\Modules\System\FileHandling\MimeTypeList;
use Artwork\Modules\System\FileHandling\RetrievesSettingsForFileType;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait HandlesFileUpload
{
    use RetrievesSettingsForFileType;


    public function handleFile(ArtworkFileTypes $type, UploadedFile $file): void
    {
        $settings = $this->retrieveSettingsForFileType($type);

        $user = Auth::user();
        $fileSize = null;
        $fileType = null;

        if (!$this->checkFileSize($settings['file_size'], $file)) {
            //throw ValidationException::withMessages(['Invalid file size ' . $file->getSize()]);
            $fileSize =  __(
                'validation.file_upload.max_size',
                ['size' => $settings['file_size']],
                $user->language
            );
        }

        if (!$this->checkMimeType($settings['mime_types'], $file)) {
            //throw ValidationException::withMessages(['Invalid file type ' . $file->getMimeType()]);
            $fileType = __(
                'validation.file_upload.invalid_file_type',
                ['format' => $file->getMimeType()],
                $user->language
            );
        }

        if ($fileSize || $fileType) {
            throw ValidationException::withMessages([
                $fileType,
                $fileSize,
            ]);
        }
    }

    private function checkMimeType(array $allowedFileTypes, UploadedFile $file): bool
    {
        $mimeTypes = $this->filterMimeTypes($allowedFileTypes);

        return in_array($file->getMimeType(), $mimeTypes, true) || in_array('*', $allowedFileTypes, true);
    }

    private function filterMimeTypes(array $allowedFileTypes): array
    {
        $allMimeTypes = array_merge(MimeTypeList::MIME_TYPES, MimeTypeList::IMAGE_MIME_TYPES);
        $allowedMimeTypes = [];

        foreach ($allowedFileTypes as $extension) {
            if (isset($allMimeTypes[$extension])) {
                $allowedMimeTypes[] = $allMimeTypes[$extension];
            }
        }

        return $allowedMimeTypes;
    }

    private function checkFileSize(int $maxFileSize, UploadedFile $file): bool
    {
        $maxFileSizeInBytes = $maxFileSize * 1048576; // Convert MB to bytes
        return $maxFileSizeInBytes >= $file->getSize();
    }
}
