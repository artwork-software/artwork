<?php

namespace Artwork\Core\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Icon-Namen sind Tabler-Exportnamen („IconHome2“) oder Slugs („home-2“). Das Frontend lädt das
 * SVG über den Namen als Dateipfad; Zeichen wie / . ? # hätten dort auf beliebige Dateien
 * (z. B. hochgeladene Bilder mit eingebettetem Skript) gezeigt.
 */
class IconNameRule implements ValidationRule
{
    public const PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail(__('The selected icon is invalid.'));
        }
    }
}
