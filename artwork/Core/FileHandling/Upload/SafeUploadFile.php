<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Upload;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Lehnt Dateien von der UploadDenyList ab (Client-Endung oder erkannter Inhalt). Nicht-Datei-Werte
 * passieren unverändert; dafür sind 'image'/'file'/'mimes' zuständig.
 */
final class SafeUploadFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $reason = UploadDenyList::deniedReason($value);
        if ($reason === null) {
            return;
        }

        $fail(DeniedUploadFileException::message($reason));
    }
}
