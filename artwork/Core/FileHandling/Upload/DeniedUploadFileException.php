<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Upload mit einer Endung/einem Inhalt von der UploadDenyList. Als ValidationException antwortet
 * Laravel im Request-Kontext von selbst mit 422 bzw. Redirect + Fehlermeldung.
 */
final class DeniedUploadFileException extends ValidationException
{
    public static function forFile(UploadedFile $file, string $field = 'file'): self
    {
        return self::forReason(UploadDenyList::deniedReason($file) ?? $file->getClientOriginalExtension(), $field);
    }

    public static function forReason(string $reason, string $field = 'file'): self
    {
        return self::withMessages([
            $field => self::message($reason),
        ]);
    }

    public static function message(string $reason): string
    {
        return __(
            'validation.file_upload.denied_file_type',
            ['format' => $reason],
            Auth::user()?->language
        );
    }
}
