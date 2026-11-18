<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\User\Models\User;
use Normalizer;

/**
 * Konto-Schlüssel der Login-Drosseln (Web-Login und App-Login).
 *
 * Die Spalte users.email vergleicht unter utf8mb4_unicode_ci, dort gelten Akzentvarianten als gleich
 * ("ánna@haus.de" = "anna@haus.de"). Ein Schlüssel aus der eingegebenen Zeichenkette ließe sich deshalb
 * mit jeder Schreibvariante neu beginnen. Der Schlüssel hängt darum am aufgelösten Konto.
 */
class LoginAccountKey
{
    /**
     * Liefert "id:<User-ID>", sobald die Eingabe in der Datenbank ein Konto trifft, sonst die
     * normalisierte Eingabe (unbekannte Adressen zählen wie bisher je Schreibweise).
     */
    public static function for(string $email): string
    {
        $normalized = self::normalize($email);

        if ($normalized === '') {
            return $normalized;
        }

        $userId = rescue(
            static fn (): mixed => User::query()->where('email', $normalized)->value('id'),
            null,
            false
        );

        return $userId !== null ? 'id:' . $userId : $normalized;
    }

    /**
     * Gleichheit zweier Adressen ohne Rücksicht auf Groß-/Kleinschreibung und umgebende Leerzeichen,
     * aber mit Rücksicht auf Akzente und Umlaute (anders als der Datenbankvergleich).
     */
    public static function sameAddress(string $first, string $second): bool
    {
        return self::normalize($first) === self::normalize($second);
    }

    /**
     * Kleinschreibung, getrimmt, in Unicode-Normalform C: ein zusammengesetzt eingegebenes "ü"
     * (etwa aus der macOS-Zwischenablage) bleibt dieselbe Adresse wie das gespeicherte "ü".
     */
    private static function normalize(string $email): string
    {
        $email = trim($email);

        if (class_exists(Normalizer::class)) {
            $composed = Normalizer::normalize($email, Normalizer::FORM_C);
            $email = is_string($composed) ? $composed : $email;
        }

        return mb_strtolower($email);
    }
}
