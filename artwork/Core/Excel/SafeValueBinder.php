<?php

declare(strict_types=1);

namespace Artwork\Core\Excel;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

/**
 * Value-Binder für alle Excel-Exporte (config/excel.php). Strings, die mit "=", "+", "-", "@", Tab oder CR
 * beginnen und nicht numerisch sind, werden als Text-Zelle gebunden (Formel-Injection); alles andere
 * geht unverändert an den DefaultValueBinder.
 */
class SafeValueBinder extends DefaultValueBinder
{
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param Cell $cell
     * @param mixed $value
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && self::isFormulaLike($value)) {
            $cell->setValueExplicit(StringHelper::sanitizeUTF8($value), DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public static function isFormulaLike(string $value): bool
    {
        if (is_numeric($value)) {
            return false;
        }

        $trimmed = ltrim($value, ' ');

        if ($trimmed === '') {
            return false;
        }

        return in_array($trimmed[0], self::DANGEROUS_PREFIXES, true);
    }
}
