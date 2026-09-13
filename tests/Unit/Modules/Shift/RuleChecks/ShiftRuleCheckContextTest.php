<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\RuleChecks\ShiftRuleCheckContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Datumsindex des Regel-Datenkontexts: shiftsBetween()/individualTimesBetween() liefern die Einträge,
 * deren EFFEKTIVER Zeitraum den Bereich berührt (Pivot-Datum vor Schichtdatum, Über-Mitternacht-Schichten
 * an beiden Tagen) — ohne zusätzliche Abfragen je Tag.
 */
final class ShiftRuleCheckContextTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private function ids(iterable $shifts): array
    {
        return collect($shifts)->map(fn (Shift $shift): int => $shift->id)->all();
    }

    #[Test]
    public function shifts_are_indexed_by_their_effective_days(): void
    {
        [$user] = $this->userWithContract();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        $tuesday = $monday->copy()->addDay();
        $wednesday = $monday->copy()->addDays(2);
        $thursday = $monday->copy()->addDays(3);
        $friday = $monday->copy()->addDays(4);
        $sunday = $monday->copy()->addDays(6);

        $dayShift = $this->shiftFor($user, $monday, '08:00:00', '16:00:00');
        $nightShift = $this->shiftFor($user, $tuesday, '22:00:00', '02:00:00', [], $wednesday);
        // Schicht am Freitag, Person laut Pivot aber am Donnerstag
        $pivotShift = $this->shiftFor($user, $friday, '10:00:00', '14:00:00');
        $this->setPivotTimes($pivotShift, $user, '10:00:00', '14:00:00', $thursday, $thursday);

        $context = ShiftRuleCheckContext::forRange($user, $monday, $sunday);

        $this->assertSame([$dayShift->id], $this->ids($context->shiftsBetween($monday->toDateString(), $monday->toDateString())));
        $this->assertSame([$nightShift->id], $this->ids($context->shiftsBetween($tuesday->toDateString(), $tuesday->toDateString())));
        // Folgetag der Nachtschicht ist belegt, der Vortag nicht
        $this->assertSame([$nightShift->id], $this->ids($context->shiftsBetween($wednesday->toDateString(), $wednesday->toDateString())));
        $this->assertSame([$pivotShift->id], $this->ids($context->shiftsBetween($thursday->toDateString(), $thursday->toDateString())));
        $this->assertSame([], $this->ids($context->shiftsBetween($friday->toDateString(), $friday->toDateString())));
        // Bereich über alles: jede Schicht genau einmal, in Ladereihenfolge
        $this->assertSame(
            [$dayShift->id, $nightShift->id, $pivotShift->id],
            $this->ids($context->shiftsBetween($monday->toDateString(), $sunday->toDateString()))
        );
        // Bereich größer als der Index (Fallback über die Indextage) liefert dasselbe
        $this->assertSame(
            [$dayShift->id, $nightShift->id, $pivotShift->id],
            $this->ids($context->shiftsBetween($monday->copy()->subYear()->toDateString(), $sunday->copy()->addYear()->toDateString()))
        );
        $this->assertSame([], $this->ids($context->shiftsBetween($sunday->toDateString(), $monday->toDateString())));
    }

    #[Test]
    public function individual_times_are_indexed_over_their_whole_span(): void
    {
        [$user] = $this->userWithContract();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        $tuesday = $monday->copy()->addDay();
        $wednesday = $monday->copy()->addDays(2);
        $thursday = $monday->copy()->addDays(3);
        $sunday = $monday->copy()->addDays(6);

        $multiDay = $this->individualTimeFor($user, $tuesday, null, null, 0, $thursday);
        $single = $this->individualTimeFor($user, $sunday, '09:00', '12:00');

        $context = ShiftRuleCheckContext::forRange($user, $monday, $sunday);

        $this->assertSame([], $context->individualTimesBetween($monday->toDateString(), $monday->toDateString())->pluck('id')->all());
        $this->assertSame([$multiDay->id], $context->individualTimesBetween($wednesday->toDateString(), $wednesday->toDateString())->pluck('id')->all());
        $this->assertSame([$multiDay->id], $context->individualTimesBetween($thursday->toDateString(), $thursday->toDateString())->pluck('id')->all());
        $this->assertSame(
            [$multiDay->id, $single->id],
            $context->individualTimesBetween($monday->toDateString(), $sunday->toDateString())->pluck('id')->all()
        );
    }

    #[Test]
    public function day_lookups_do_not_query_after_the_index_is_built(): void
    {
        [$user] = $this->userWithContract();
        $monday = $this->futureWeekday(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);
        $this->shiftFor($user, $monday);
        $this->shiftFor($user, $monday->copy()->addDays(2), '22:00:00', '02:00:00', [], $monday->copy()->addDays(3));
        $this->individualTimeFor($user, $monday->copy()->addDays(4), '09:00', '12:00');

        $context = ShiftRuleCheckContext::forRange($user, $monday, $sunday);
        $context->shiftsBetween($monday->toDateString(), $monday->toDateString());
        $context->individualTimesBetween($monday->toDateString(), $monday->toDateString());

        DB::flushQueryLog();
        DB::enableQueryLog();
        for ($day = $monday->copy(); $day->lte($sunday); $day->addDay()) {
            $context->shiftsBetween($day->toDateString(), $day->toDateString());
            $context->individualTimesBetween($day->toDateString(), $day->toDateString());
        }
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries);
    }
}
