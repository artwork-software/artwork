<?php

namespace Artwork\Core\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Farben landen in PDF-Exporten direkt im style-Attribut. Nur Hex-Werte (#rgb, #rgba, #rrggbb,
 * #rrggbbaa), sonst ließen sich weitere CSS-Eigenschaften einschleusen (z. B. url() für Abrufe
 * beim PDF-Rendern).
 */
class HexColorRule implements ValidationRule
{
    public const PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail(__('The selected color is invalid.'));
        }
    }
}
