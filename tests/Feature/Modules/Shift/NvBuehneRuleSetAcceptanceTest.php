<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Support\NvBuehneStageShiftRules;
use Artwork\Modules\Shift\Support\ShiftRuleSets;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

/**
 * Abnahmetest des Regelsatzes "NV Bühne (Bühnentechnik)" über den ganzen Weg: Regelsatz über den
 * Endpunkt anlegen, einem Vertrag zuweisen, eine geplante Woche durch den ShiftRuleService prüfen.
 * Erwartet wird je Regel genau der Verstoß, den der Tarif für diese Woche hergibt, und kein weiterer.
 */
final class NvBuehneRuleSetAcceptanceTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Regeln gleichen Typs im Bestand würden über die Idempotenz des Regelsatzes das Anlegen verhindern.
     */
    private function createRuleSetFor(UserContract $contract): void
    {
        ShiftRule::query()
            ->whereIn('trigger_type', collect(NvBuehneStageShiftRules::all())->pluck('trigger_type'))
            ->update(['deleted_at' => now()]);
        Bus::fake();

        $this->actingAsAdmin();
        $this->post(route('shift-rules.defaults.store'), [
            'set' => ShiftRuleSets::NV_BUEHNE_STAGE,
            'rules' => ShiftRuleSets::ruleKeys(ShiftRuleSets::NV_BUEHNE_STAGE),
            'contract_ids' => [$contract->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function grantHalfDay(User $user, Carbon $day, string $period, Carbon $grantedAt): void
    {
        CompensationDayOff::create([
            'user_id' => $user->id,
            'value' => 0.5,
            'half_day_period' => $period,
            'granted_date' => $day->toDateString(),
            'granted_at' => $grantedAt,
            'deadline' => $day->copy()->addDays(30)->toDateString(),
        ]);
    }

    /**
     * @return array<string, int> Anzahl der Verstöße der Person je Regeltyp
     */
    private function violationsByType(User $user, Carbon $from, Carbon $to): array
    {
        app(ShiftRuleService::class)->validateRulesForUser($user, $from->copy(), $to->copy());

        return ShiftRuleViolation::query()
            ->where('user_id', $user->id)
            ->with('shiftRule')
            ->get()
            ->groupBy(fn (ShiftRuleViolation $violation): string => $violation->shiftRule->trigger_type)
            ->map->count()
            ->sortKeys()
            ->all();
    }

    #[Test]
    public function a_planned_week_produces_exactly_the_violations_the_agreement_defines(): void
    {
        // Freitag nach der Wochenplan-Frist (Donnerstag 14.01.2027, 14 Uhr)
        Carbon::setTestNow(Carbon::parse('2027-01-15 10:00:00'));
        [$user, $contract] = $this->userWithContract();
        $this->createRuleSetFor($contract);

        $monday = Carbon::parse('2027-01-18');
        $day = static fn (int $offset): Carbon => $monday->copy()->addDays($offset);

        // Montag: geteilter Dienst mit nur 2 Stunden Abstand
        $this->shiftFor($user, $day(0), '08:00:00', '12:00:00');
        $this->shiftFor($user, $day(0), '14:00:00', '18:00:00');
        // Dienstag: Beginn 04:00 nach Dienstende 18:00, nur 10 Stunden Nachtruhe
        $this->shiftFor($user, $day(1), '04:00:00', '10:00:00');
        // Mittwoch: freier Vormittag gewährt, Dienst beginnt trotzdem um 10 Uhr
        $this->shiftFor($user, $day(2), '10:00:00', '16:00:00');
        $this->grantHalfDay($user, $day(2), 'morning', Carbon::now());
        // Donnerstag: freier Vormittag gewährt, am Nachmittag 5,5 Stunden Arbeit
        $this->shiftFor($user, $day(3), '14:00:00', '19:30:00');
        $this->grantHalfDay($user, $day(3), 'morning', Carbon::now());
        // Freitag und Samstag: normale Dienste, damit kein Werktag frei bleibt
        $this->shiftFor($user, $day(4), '10:00:00', '16:00:00');
        $this->shiftFor($user, $day(5), '10:00:00', '16:00:00');
        // Sonntag: freier Vormittag gewährt, 4 Stunden Dienst am Nachmittag
        $this->shiftFor($user, $day(6), '14:00:00', '18:00:00');
        $this->grantHalfDay($user, $day(6), 'morning', Carbon::now());

        $violations = $this->violationsByType($user, Carbon::parse('2027-01-11'), Carbon::parse('2027-01-31'));

        $this->assertSame([
            // drei halbe freie Tage, alle erst drei bis neun Tage vorher gewährt (§ 66 Abs. 7)
            'freeDayAnnouncementLeadTime' => 3,
            // Mittwoch: Dienst reicht in den freien Vormittag (§ 66 Abs. 4)
            'halfDayOffConflict' => 1,
            // Donnerstag: mehr als 4 Stunden in der anderen Tageshälfte (§ 66 Abs. 4)
            'halfDayOffMaxHours' => 1,
            // Sonntag: halber freier Tag am Sonntagvormittag (§ 66 Abs. 4)
            'halfDayOffOnSpecialDay' => 1,
            // kein freier Werktag in der Woche (§ 66 Abs. 1)
            'minFreeDaysPerWeek' => 1,
            // Dienstag: Nachtruhe unter 11 Stunden (§ 65 Abs. 1), der geteilte Montag zählt hier nicht
            'restTimeBeforeWorkday' => 1,
            // Montag: geteilter Dienst unter 4 Stunden Abstand (§ 65 Abs. 2)
            'restTimeBetweenShiftsOnDay' => 1,
            // Dienste der Woche nach Donnerstag 14 Uhr noch nicht verbindlich (§ 6 Abs. 1)
            'weeklyPlanCommitDeadline' => 1,
        ], $violations);
    }

    #[Test]
    public function violations_disappear_when_the_plan_is_corrected(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-01-15 10:00:00'));
        [$user, $contract] = $this->userWithContract();
        $this->createRuleSetFor($contract);

        $monday = Carbon::parse('2027-01-18');
        $first = $this->shiftFor($user, $monday, '08:00:00', '12:00:00');
        $second = $this->shiftFor($user, $monday, '14:00:00', '18:00:00');
        $from = Carbon::parse('2027-01-11');
        $to = Carbon::parse('2027-01-31');

        $this->assertSame(
            ['restTimeBetweenShiftsOnDay' => 1, 'weeklyPlanCommitDeadline' => 1],
            $this->violationsByType($user, $from, $to)
        );

        // Plan korrigieren: Abstand auf 4 Stunden, Woche verbindlich
        $second->update(['start' => '16:00:00', 'end' => '20:00:00']);
        Shift::query()->whereIn('id', [$first->id, $second->id])->update(['is_committed' => true]);

        $this->assertSame([], $this->violationsByType($user, $from, $to));
    }

    #[Test]
    public function a_well_planned_week_stays_free_of_violations(): void
    {
        // Mittwoch vor der Frist: der Wochenplan für die Folgewoche darf noch offen sein
        Carbon::setTestNow(Carbon::parse('2027-01-13 09:00:00'));
        [$user, $contract] = $this->userWithContract();
        $this->createRuleSetFor($contract);

        $monday = Carbon::parse('2027-01-18');
        // Montag bis Freitag 09–17 Uhr, Samstag geteilter Dienst mit 4 Stunden Abstand, Sonntag frei
        foreach (range(0, 4) as $offset) {
            $this->shiftFor($user, $monday->copy()->addDays($offset), '09:00:00', '17:00:00');
        }
        $saturday = $monday->copy()->addDays(5);
        $this->shiftFor($user, $saturday, '09:00:00', '12:00:00');
        $this->shiftFor($user, $saturday, '16:00:00', '20:00:00');

        $violations = $this->violationsByType($user, Carbon::parse('2027-01-11'), Carbon::parse('2027-01-31'));

        // Der Sonntag ist frei, aber kein Werktag: mit "nur Werktage" fehlt der freie Werktag (§ 66 Abs. 1)
        $this->assertSame(['minFreeDaysPerWeek' => 1], $violations);
    }

    #[Test]
    public function work_on_christmas_eve_requires_a_free_first_of_may(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-04-20 09:00:00'));
        [$user, $contract] = $this->userWithContract();
        $this->createRuleSetFor($contract);

        $this->shiftFor($user, Carbon::parse('2026-12-24'), '10:00:00', '14:00:00', ['is_committed' => true]);
        $this->shiftFor($user, Carbon::parse('2027-05-01'), '10:00:00', '14:00:00', ['is_committed' => true]);

        $violations = $this->violationsByType($user, Carbon::parse('2027-04-26'), Carbon::parse('2027-05-02'));

        $this->assertSame(1, $violations['freeDayAfterWorkOnDate'] ?? 0);
    }

    #[Test]
    public function weekly_plan_violations_of_past_weeks_are_cleaned_up(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-01-15 10:00:00'));
        [$user, $contract] = $this->userWithContract();
        $this->createRuleSetFor($contract);
        $rule = ShiftRule::query()->where('trigger_type', 'weeklyPlanCommitDeadline')->sole();

        // Stehen gebliebener Verstoß einer Woche, die längst vorbei ist
        ShiftRuleViolation::create([
            'shift_rule_id' => $rule->id,
            'shift_id' => null,
            'user_id' => $user->id,
            'violation_date' => '2026-12-28',
            'violation_data' => ['type' => 'weekly_plan_commit_deadline', 'open_shifts' => 1],
            'severity' => 'warning',
            'status' => 'active',
        ]);

        $this->assertSame([], $this->violationsByType($user, Carbon::parse('2027-01-11'), Carbon::parse('2027-01-31')));
    }
}
