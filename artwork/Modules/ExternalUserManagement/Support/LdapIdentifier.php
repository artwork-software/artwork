<?php

namespace Artwork\Modules\ExternalUserManagement\Support;

use InvalidArgumentException;
use LdapRecord\Models\Attributes\Guid;
use LdapRecord\Models\Attributes\Sid;

/**
 * Normalisiert LDAP-Attributwerte zu JSON- und datenbanksicheren Strings.
 *
 * Active Directory liefert objectGUID und objectSid binär aus. Roh weitergereicht
 * sprengt so ein Wert sowohl json_encode() (ungültiges UTF-8) als auch die
 * utf8mb4-Spalte external_users.identification.
 */
class LdapIdentifier
{
    /**
     * Wandelt den Rohwert des konfigurierten identifier_attribute in seine
     * lesbare, stabile String-Form um.
     *
     * Guid/Sid akzeptieren beide Richtungen (bereits konvertierter String oder
     * Binärwert), die Konvertierung ist damit idempotent – wichtig, weil derselbe
     * Wert beim Login und beim Sync durch dieselbe Normalisierung laufen muss.
     */
    public static function normalize(string $attribute, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = match (mb_strtolower($attribute)) {
            'objectguid' => self::isGuidValue($value)
                ? self::convert(static fn (): string => (new Guid($value))->getValue(), $value)
                : $value,
            'objectsid' => self::isSidValue($value)
                ? self::convert(static fn (): string => (new Sid($value))->getValue(), $value)
                : $value,
            default => $value,
        };

        return self::safeString($normalized);
    }

    /**
     * Liefert den Filterwert fuer eine Suche nach dem Identifier – oder null, wenn
     * der Wert unveraendert in ein normales where() gehoert.
     *
     * Active Directory vergleicht objectGUID binaer: im Filter muss die escapte
     * Hex-Form (\\c3\\d8\\8d\\6f…) stehen, ein kanonischer GUID-String findet nichts.
     * LdapRecords findByGuid() uebernimmt das nur fuer ActiveDirectory-Modelle,
     * hier wird aber Models\\Entry verwendet – deshalb explizit.
     */
    public static function toFilterValue(string $attribute, string $value): ?string
    {
        return match (mb_strtolower($attribute)) {
            'objectguid' => self::isGuidValue($value) ? self::guidFilterValue($value) : null,
            // objectSid wird von AD ebenfalls binär verglichen — der kanonische
            // S-1-5-…-String aus normalize() fände sonst nichts.
            'objectsid' => Sid::isValid($value) ? self::sidFilterValue($value) : null,
            default => null,
        };
    }

    private static function guidFilterValue(string $value): ?string
    {
        try {
            return (new Guid($value))->getEncodedHex();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private static function sidFilterValue(string $value): ?string
    {
        try {
            $binary = (new Sid($value))->getBinary();
        } catch (InvalidArgumentException) {
            return null;
        }

        return '\\' . implode('\\', str_split(bin2hex($binary), 2));
    }


    private static function isGuidValue(string $value): bool
    {
        return Guid::isValid($value) || strlen($value) === 16;
    }

    /**
     */
    private static function isSidValue(string $value): bool
    {
        if (Sid::isValid($value)) {
            return true;
        }

        return strlen($value) >= 8
            && ord($value[0]) === 1
            && strlen($value) === 8 + (ord($value[1]) * 4);
    }

    public static function safeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_check_encoding($value, 'UTF-8') ? $value : bin2hex($value);
    }

    /**
     * @param callable(): string $converter
     */
    private static function convert(callable $converter, string $fallback): string
    {
        try {
            return $converter();
        } catch (InvalidArgumentException) {
            // Weder gültiger String noch gültiger Binärwert – der Fallback läuft
            // anschließend durch safeString() und wird notfalls hex-kodiert.
            return $fallback;
        }
    }
}
