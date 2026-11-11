<?php

namespace Artwork\Modules\AiAssistant\Support;

/**
 * Fingerabdruck der Argumente einer bestaetigten Aktion (core-confirm-actions; Konzept W2 §4).
 *
 * Der Hash ist die eigentliche Sicherung des Bestaetigungs-Pfades: artwork vergleicht ihn mit dem,
 * was die Person in der Vorschau gesehen hat. Weder ein manipuliertes artwork-ai noch ein
 * „nachbesserndes" Modell kann damit etwas ausfuehren, das nicht exakt so bestaetigt wurde.
 *
 * Kanonische Form (artwork-ai muss dieselbe Regel anwenden):
 * 1. Objektschluessel rekursiv alphabetisch sortieren; die Reihenfolge von Listen bleibt.
 * 2. `json_encode` mit `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
 * 3. `sha256` davon, als Hex in Kleinbuchstaben.
 */
final class AiActionHash
{
    /**
     * @param array<string, mixed> $args
     */
    public static function of(array $args): string
    {
        return hash('sha256', self::canonicalize($args));
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function matches(array $args, string $expected): bool
    {
        return hash_equals(self::of($args), strtolower($expected));
    }

    /**
     * @param mixed $value
     */
    private static function canonicalize(mixed $value): string
    {
        return (string) json_encode(
            self::normalize($value),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    private static function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = array_map(self::normalize(...), $value);

        // Listen behalten ihre Reihenfolge — sie ist Teil der Bedeutung. Objekte werden sortiert,
        // damit dieselben Argumente in anderer Schluesselreihenfolge denselben Hash ergeben.
        if (!array_is_list($normalized)) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }
}
