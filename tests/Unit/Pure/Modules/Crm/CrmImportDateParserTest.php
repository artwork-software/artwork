<?php

namespace Tests\Unit\Pure\Modules\Crm;

use Artwork\Modules\Crm\Support\CrmImportDateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class CrmImportDateParserTest extends UnitTestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function validDates(): array
    {
        return [
            'german with four digit year' => ['15.03.2026', '2026-03-15'],
            'german with two digit year' => ['15.03.26', '2026-03-15'],
            'german two digit year of the last century' => ['01.02.85', '1985-02-01'],
            'german without leading zeros' => ['5.3.2026', '2026-03-05'],
            'iso' => ['2026-03-15', '2026-03-15'],
            'iso with time' => ['2026-03-15 14:30:00', '2026-03-15'],
            'european with slashes' => ['31/12/2026', '2026-12-31'],
            'european with slashes is day first' => ['03/04/2026', '2026-04-03'],
            'excel serial number' => ['46096', '2026-03-15'],
            'excel serial number with time' => ['46096.604166666664', '2026-03-15'],
            'surrounding whitespace' => [' 15.03.2026 ', '2026-03-15'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDates(): array
    {
        return [
            'american month first' => ['12/31/2026'],
            'slashes with two digit year' => ['31/12/26'],
            'impossible day' => ['31.02.2026'],
            'impossible month' => ['2026-13-01'],
            'month name' => ['15. März 2026'],
            'relative text' => ['tomorrow'],
            'time only' => ['15:03:26'],
            'bare year' => ['2026'],
            'zero' => ['0'],
            'number beyond the excel range' => ['20260315'],
            'negative number' => ['-5'],
            'empty' => [''],
        ];
    }

    #[Test]
    #[DataProvider('validDates')]
    public function it_reads_every_supported_notation(string $value, string $expected): void
    {
        $this->assertSame($expected, CrmImportDateParser::parse($value));
    }

    #[Test]
    #[DataProvider('invalidDates')]
    public function it_rejects_everything_else_instead_of_guessing(string $value): void
    {
        $this->assertFalse(CrmImportDateParser::parse($value));
    }
}
