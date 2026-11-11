<?php

namespace Artwork\Modules\AiAssistant\Support;

use Illuminate\Support\Str;

/**
 * Freitext fuer die Lese-Endpunkte kuerzen (Konzept B4 §4).
 *
 * Die Kuerzung sitzt bewusst in artwork und nicht in artwork-ai: so gilt die Grenze fuer jeden
 * Konsumenten der Endpunkte, nicht nur fuer den, der sich daran haelt. Sie ist kein Schutz gegen
 * Prompt-Injection — der liegt in B4 §5 — sondern begrenzt die Menge an Fremdtext im Modellkontext.
 */
final class AiText
{
    public static function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit(trim($value), (int) config('ai.text_limit'), '…');
    }
}
