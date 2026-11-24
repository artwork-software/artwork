<?php

namespace Artwork\Modules\AiAssistant\Support;

/**
 * Zellwerte fuer Tabellen-Exporte (core-ai-export-formats).
 *
 * Die Zeilen stammen aus Werkzeugergebnissen und damit letztlich aus Nutzereingaben in artwork.
 * Tabellenprogramme fuehren Zellen, die mit `=`, `+`, `-`, `@`, Tab oder CR beginnen, als Formel aus
 * (CSV-/Formula-Injection). Solche Werte bekommen ein fuehrendes `'`, ausser es ist eine reine Zahl.
 */
final class AiExportCell
{
    private const NUMBER = '/^-?\d+(\.\d+)?$/';

    private const FORMULA_START = ['=', '+', '-', '@', "\t", "\r"];

    public static function isNumber(string $value): bool
    {
        return preg_match(self::NUMBER, $value) === 1;
    }

    /** Wert fuer CSV und Text-Zellen: Formelanfaenge neutralisiert, Zahlen unveraendert. */
    public static function safe(string $value): string
    {
        if ($value === '' || self::isNumber($value)) {
            return $value;
        }

        return in_array($value[0], self::FORMULA_START, true) ? "'" . $value : $value;
    }
}
