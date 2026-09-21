<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Upload;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validierungsregel für Upload-Pfade ohne handleFile(): lehnt jede Datei ab, die auf der
 * UploadDenyList steht (Client-Endung ODER erkannter Inhalt). Nicht-Datei-Werte (z. B. eine
 * bereits gespeicherte Bild-URL als String) passieren die Regel unverändert - dafür sind
 * 'image' / 'file' / 'mimes' zuständig.
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
