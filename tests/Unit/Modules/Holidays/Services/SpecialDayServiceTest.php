<?php

namespace Tests\Unit\Modules\Holidays\Services;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Services\SpecialDayService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SpecialDayServiceTest extends TestCase
{
    private function holiday(array $attributes): Holiday
    {
        return Holiday::create(array_merge([
            'name' => 'Feiertag',
            'yearly' => false,
            'from_api' => false,
            'treatAsSpecialDay' => true,
        ], $attributes));
    }

    private function service(): SpecialDayService
    {
        return app(SpecialDayService::class);
    }

    #[Test]
    public function only_holidays_with_the_flag_are_special_days(): void
    {
        $this->holiday([
            'name' => 'Feiertag',
            'date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'treatAsSpecialDay' => true,
        ]);
        $this->holiday([
            'name' => 'Ferien',
            'date' => '2026-07-22',
            'end_date' => '2026-07-22',
            'treatAsSpecialDay' => false,
        ]);

        $service = $this->service();

        $this->assertTrue($service->isSpecialDay('2026-07-21'));
        $this->assertFalse($service->isSpecialDay('2026-07-22'));
        $this->assertFalse($service->isSpecialDay('2026-07-23'));
        $this->assertSame('Feiertag', $service->specialDayName('2026-07-21'));
        $this->assertNull($service->specialDayName('2026-07-22'));
    }

    #[Test]
    public function multi_day_entries_cover_every_day_of_the_range(): void
    {
        $this->holiday(['name' => 'Festtage', 'date' => '2026-12-24', 'end_date' => '2026-12-26']);

        $service = $this->service();

        $this->assertFalse($service->isSpecialDay('2026-12-23'));
        $this->assertTrue($service->isSpecialDay('2026-12-24'));
        $this->assertTrue($service->isSpecialDay('2026-12-25'));
        $this->assertTrue($service->isSpecialDay('2026-12-26'));
        $this->assertFalse($service->isSpecialDay('2026-12-27'));

        $between = $service->specialDaysBetween(Carbon::parse('2026-12-20'), Carbon::parse('2026-12-31'));
        $this->assertSame(['2026-12-24', '2026-12-25', '2026-12-26'], array_keys($between));
        $this->assertSame('Festtage', $between['2026-12-25']);
    }

    #[Test]
    public function yearly_leap_day_only_counts_in_leap_years(): void
    {
        $this->holiday(['name' => 'Schalttag', 'date' => '2024-02-29', 'end_date' => '2024-02-29', 'yearly' => true]);

        $service = $this->service();

        $this->assertTrue($service->isSpecialDay('2028-02-29'));
        // vorher lief der 29.02. in Nicht-Schaltjahren auf den 01.03. über
        $this->assertFalse($service->isSpecialDay('2027-03-01'));
        $this->assertFalse($service->isSpecialDay('2027-02-28'));
    }

    #[Test]
    public function yearly_entries_repeat_and_may_span_the_turn_of_the_year(): void
    {
        $this->holiday([
            'name' => 'Jahreswechsel',
            'date' => '2020-12-31',
            'end_date' => '2021-01-01',
            'yearly' => true,
        ]);
        $this->holiday([
            'name' => 'Neujahr-Sonder',
            'date' => '2019-05-01',
            'end_date' => '2019-05-01',
            'yearly' => true,
        ]);

        $service = $this->service();

        $this->assertTrue($service->isSpecialDay('2026-12-31'));
        $this->assertTrue($service->isSpecialDay('2027-01-01'));
        $this->assertFalse($service->isSpecialDay('2026-12-30'));
        $this->assertFalse($service->isSpecialDay('2027-01-02'));
        $this->assertTrue($service->isSpecialDay('2026-05-01'));
        $this->assertTrue($service->isSpecialDay('2031-05-01'));
    }

    #[Test]
    public function contract_rule_on_the_assignment_decides_whether_special_days_count_for_the_user(): void
    {
        $this->holiday(['date' => '2026-07-21', 'end_date' => '2026-07-21']);
        $template = UserContract::create([
            'name' => 'Vorlage',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);

        $active = User::factory()->create();
        UserContractAssign::create([
            'user_id' => $active->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
        $inactive = User::factory()->create();
        UserContractAssign::create([
            'user_id' => $inactive->id,
            'user_contract_id' => $template->id,
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => false,
            'compensation_period' => 90,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0,
        ]);
        $withoutContract = User::factory()->create();

        $service = $this->service();

        $this->assertTrue($service->countsAsSpecialDayForUser($active, '2026-07-21'));
        $this->assertFalse($service->countsAsSpecialDayForUser($inactive, '2026-07-21'));
        $this->assertTrue($service->countsAsSpecialDayForUser($withoutContract, '2026-07-21'));
        $this->assertFalse($service->countsAsSpecialDayForUser($active, '2026-07-22'));
    }

    #[Test]
    public function assignment_falls_back_to_the_template_when_its_own_flag_is_unset(): void
    {
        $this->holiday(['date' => '2026-07-21', 'end_date' => '2026-07-21']);
        $template = new UserContract(['name' => 'Vorlage', 'special_day_rule_active' => false]);
        $assign = new UserContractAssign();
        $assign->setRelation('userContract', $template);

        $user = User::factory()->create();
        $user->setRelation('contract', $assign);

        $this->assertFalse($this->service()->countsAsSpecialDayForUser($user, '2026-07-21'));

        $assign->special_day_rule_active = true;
        $this->assertTrue($this->service()->countsAsSpecialDayForUser($user, '2026-07-21'));
    }

    /**
     * Absicherung des vorberechneten Grenzen-Caches: Ergebnis muss für jeden Tag exakt der bisherigen
     * Auswertung (Parsen je Tag × Eintrag) entsprechen — mehrtägig, jährlich, über den Jahreswechsel,
     * 29.02., fehlendes bzw. vor dem Start liegendes Enddatum, Reihenfolge bei Überschneidungen.
     */
    #[Test]
    public function precomputed_bounds_match_the_reference_evaluation_for_every_day(): void
    {
        $this->holiday(['name' => 'Einzeltag', 'date' => '2026-07-21', 'end_date' => '2026-07-21']);
        $this->holiday(['name' => 'Ohne Ende', 'date' => '2026-03-03', 'end_date' => null]);
        $this->holiday(['name' => 'Ende vor Start', 'date' => '2026-04-10', 'end_date' => '2026-04-08']);
        $this->holiday(['name' => 'Block', 'date' => '2026-12-24', 'end_date' => '2027-01-02']);
        $this->holiday(['name' => 'Überlappt Block', 'date' => '2026-12-31', 'end_date' => '2026-12-31']);
        $this->holiday(['name' => 'Jährlich', 'date' => '2019-05-01', 'end_date' => '2019-05-01', 'yearly' => true]);
        $this->holiday([
            'name' => 'Jährlich Jahreswechsel',
            'date' => '2020-12-30',
            'end_date' => '2021-01-02',
            'yearly' => true,
        ]);
        $this->holiday(['name' => 'Schalttag', 'date' => '2024-02-29', 'end_date' => '2024-02-29', 'yearly' => true]);
        $this->holiday([
            'name' => 'Jährlich ohne Ende',
            'date' => '2022-10-03',
            'end_date' => null,
            'yearly' => true,
        ]);
        $this->holiday([
            'name' => 'Kein Sondertag',
            'date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'treatAsSpecialDay' => false,
        ]);

        $flagged = Holiday::query()
            ->where('treatAsSpecialDay', true)
            ->get(['id', 'name', 'date', 'end_date', 'yearly']);
        $service = $this->service();
        $from = Carbon::parse('2023-12-01');
        $to = Carbon::parse('2028-03-31');

        $expected = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $name = $this->referenceMatchingHoliday($flagged, $day->copy()->startOfDay())?->name;
            if ($name !== null) {
                $expected[$day->toDateString()] = $name;
            }
        }

        $this->assertNotEmpty($expected);
        $this->assertSame($expected, $service->specialDaysBetween($from, $to));

        $fresh = app(SpecialDayService::class);
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $this->assertSame(isset($expected[$key]), $fresh->isSpecialDay($key), $key);
            $this->assertSame($expected[$key] ?? null, $fresh->specialDayName($key), $key);
        }
    }

    /**
     * Bisherige Auswertung (vor dem Grenzen-Cache) als Referenz.
     *
     * @param \Illuminate\Support\Collection<int, Holiday> $holidays
     */
    private function referenceMatchingHoliday(\Illuminate\Support\Collection $holidays, Carbon $day): ?Holiday
    {
        foreach ($holidays as $holiday) {
            $start = Carbon::parse($holiday->date)->startOfDay();
            $end = $holiday->end_date ? Carbon::parse($holiday->end_date)->startOfDay() : $start->copy();
            if ($end->lt($start)) {
                $end = $start->copy();
            }

            if (!$holiday->yearly) {
                if ($day->betweenIncluded($start, $end)) {
                    return $holiday;
                }
                continue;
            }

            $spanDays = (int) $start->diffInDays($end);
            foreach ([$day->year, $day->year - 1] as $candidateYear) {
                $candidateStart = Holiday::yearlyStartIn($start, $candidateYear);
                if (
                    $candidateStart !== null
                    && $day->betweenIncluded($candidateStart, $candidateStart->copy()->addDays($spanDays))
                ) {
                    return $holiday;
                }
            }
        }

        return null;
    }
}
