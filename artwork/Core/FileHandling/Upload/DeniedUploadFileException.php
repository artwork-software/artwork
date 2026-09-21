<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Upload mit einer Endung / einem Inhalt von der UploadDenyList.
 *
 * Bewusst eine ValidationException: im Request-Kontext antwortet Laravel damit von selbst
 * mit 422 (JSON) bzw. Redirect + Fehlermeldung (Inertia) - auch dort, wo StoredFileName
 * ohne vorgeschaltete Validierung direkt vor storeAs() aufgerufen wird.
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
