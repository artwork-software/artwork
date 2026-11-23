<?php

namespace Tests\Unit\Core;

use Artwork\Core\Carbon\Service\CarbonService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Die Excel-Exporte von Kalender und Terminliste fragen das Datumsformat zur Sprache der Person ab.
 */
final class CarbonServiceDateFormatTest extends TestCase
{
    #[Test]
    public function everyLanguageGetsADateFormat(): void
    {
        $service = new CarbonService(new Carbon(), new CarbonPeriod());

        $this->assertSame(CarbonService::GERMAN_DATE_FORMAT, $service->getDesiredDateFormatFromLocale('de'));
        $this->assertSame(CarbonService::INTERNATIONAL_DATE_FORMAT, $service->getDesiredDateFormatFromLocale('en'));
        // weitere Sprachen (nl) führten zu einem UnhandledMatchError und damit zu einem Fehler beim Export
        $this->assertSame(CarbonService::INTERNATIONAL_DATE_FORMAT, $service->getDesiredDateFormatFromLocale('nl'));
    }
}
