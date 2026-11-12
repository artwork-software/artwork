<?php

namespace Artwork\Modules\Crm\Support;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Liest Datumswerte des CRM-Imports streng und vorhersehbar.
 *
 * Gültig sind TT.MM.JJJJ, TT.MM.JJ, JJJJ-MM-TT, TT/MM/JJJJ (europäisch, Tag vor Monat) und
 * Excel-Seriennummern, jeweils mit oder ohne Uhrzeit; die Uhrzeit wird verworfen. Zweistellige Jahre
 * von 00 bis 69 gelten als 2000 bis 2069, 70 bis 99 als 1970 bis 1999. Als Seriennummer zählt nur
 * eine Zahl ohne führende Null, die ein Datum ab dem 01.01.1930 ergibt; Nachkommastellen sind dabei
 * der Uhrzeitanteil. Kleinere Zahlen ("5", "12.5", "12.05", "1899", "2101", "010124") sind fast
 * immer etwas anderes (Tag, Tag und Monat ohne Jahr, Jahreszahl, Datum ohne Trennzeichen). Alles
 * andere ist ein ungültiger Wert und wird nie zu einem geratenen Datum.
 */
final class CrmImportDateParser
{
    private const TIME = '(?:[ T]\d{1,2}:\d{2}(?::\d{2})?)?';

    /**
     * Seriennummer des 01.01.1930. Alles darunter wird nicht als Seriennummer gelesen.
     */
    private const MIN_EXCEL_SERIAL = 10959;

    private const MAX_EXCEL_SERIAL = 2958465;

    public static function parse(string $value): string|false
    {
        $value = trim($value);

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4}|\d{2})' . self::TIME . '$/', $value, $match) === 1) {
            return self::fromParts(self::fullYear($match[3]), (int) $match[2], (int) $match[1]);
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})' . self::TIME . '$/', $value, $match) === 1) {
            return self::fromParts((int) $match[1], (int) $match[2], (int) $match[3]);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})' . self::TIME . '$/', $value, $match) === 1) {
            return self::fromParts((int) $match[3], (int) $match[2], (int) $match[1]);
        }

        if (preg_match('/^[1-9]\d*(?:\.\d+)?$/', $value) === 1) {
            return self::fromExcelSerial($value);
        }

        return false;
    }

    private static function fullYear(string $year): int
    {
        if (strlen($year) === 4) {
            return (int) $year;
        }

        return (int) $year + ((int) $year < 70 ? 2000 : 1900);
    }

    private static function fromParts(int $year, int $month, int $day): string|false
    {
        if (!checkdate($month, $day, $year)) {
            return false;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private static function fromExcelSerial(string $value): string|false
    {
        $serial = (float) $value;

        if ($serial < self::MIN_EXCEL_SERIAL || $serial >= self::MAX_EXCEL_SERIAL + 1) {
            return false;
        }

        return ExcelDate::excelToDateTimeObject($serial, 'UTC')->format('Y-m-d');
    }
}
