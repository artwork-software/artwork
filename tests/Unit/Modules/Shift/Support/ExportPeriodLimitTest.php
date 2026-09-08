<?php

namespace Tests\Unit\Modules\Shift\Support;

use Artwork\Modules\Shift\Support\ExportPeriodLimit;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ExportPeriodLimit: resolveBounds() füllt fehlende Grenzen deterministisch auf (Export nie unbegrenzt)
 * und wirft bei > 366 Tagen; clampForList() begrenzt statt zu werfen (Inertia-Liste).
 */
final class ExportPeriodLimitTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function resolve_bounds_defaults_to_the_current_month_when_both_bounds_are_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 12:00:00', 'Europe/Berlin'));

        [$from, $to] = ExportPeriodLimit::resolveBounds(null, '');

        $this->assertSame('2026-09-01', $from->toDateString());
        $this->assertSame('2026-09-30', $to->toDateString());
        $this->assertSame('00:00:00', $from->toTimeString());
        $this->assertSame('23:59:59', $to->toTimeString());
    }

    #[Test]
    public function resolve_bounds_fills_the_missing_bound_at_a_distance_of_the_maximum_period(): void
    {
        [$from, $to] = ExportPeriodLimit::resolveBounds('2020-01-01', null);
        $this->assertSame('2020-01-01', $from->toDateString());
        $this->assertSame('2021-01-01', $to->toDateString());

        [$from, $to] = ExportPeriodLimit::resolveBounds(null, '2021-01-01');
        $this->assertSame('2020-01-01', $from->toDateString());
        $this->assertSame('2021-01-01', $to->toDateString());
    }

    #[Test]
    public function resolve_bounds_keeps_a_given_period_and_rejects_more_than_a_year(): void
    {
        [$from, $to] = ExportPeriodLimit::resolveBounds('2025-01-01', '2026-01-02');
        $this->assertSame('2025-01-01', $from->toDateString());
        $this->assertSame('2026-01-02', $to->toDateString());

        try {
            ExportPeriodLimit::resolveBounds('2025-01-01', '2026-01-03', 'end_date');
            $this->fail('Zeitraum länger als ein Jahr wurde akzeptiert');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('end_date', $exception->errors());
        }
    }

    #[Test]
    public function clamp_for_list_limits_the_end_to_one_year_after_the_start_without_throwing(): void
    {
        $this->assertSame(
            ['date_from' => '2025-01-01', 'date_to' => '2026-01-02', 'period_clamped' => true],
            ExportPeriodLimit::clampForList('2025-01-01', '2026-06-30')
        );
        $this->assertSame(
            ['date_from' => '2025-01-01', 'date_to' => '2026-01-02', 'period_clamped' => false],
            ExportPeriodLimit::clampForList('2025-01-01', '2026-01-02')
        );
        // Einzelne/fehlende Grenzen bleiben (Liste ohne Zeitraum = alle Einträge des Status)
        $this->assertSame(
            ['date_from' => '2020-01-01', 'date_to' => null, 'period_clamped' => false],
            ExportPeriodLimit::clampForList('2020-01-01', '')
        );
        $this->assertSame(
            ['date_from' => null, 'date_to' => null, 'period_clamped' => false],
            ExportPeriodLimit::clampForList(null, null)
        );
    }
}
