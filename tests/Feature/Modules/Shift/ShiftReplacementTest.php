<?php

namespace Tests\Feature\Modules\Shift;

use App\Settings\ShiftSettings;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Shift\Services\ShiftReplacementService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Feature\FeatureTestCase;

/**
 * Block 5C: „Ersatz suchen" nach Absage — Kandidat*innen (Gewerk/Funktion,
 * Urlaub, Überschneidung, Saldo-Sortierung, konstantes Query-Budget) und
 * Ersetzen in einer Transaktion über die bestehenden Remove-/Assign-Pfade.
 */
final class ShiftReplacementTest extends FeatureTestCase
{
    private const DATE = '2026-06-08';

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(ShiftSettings::class);
        $settings->shift_confirmation_enabled = true;
        $settings->save();
    }

    /* ---------------- Kandidat*innen ---------------- */

    #[Test]
    public function candidates_match_craft_and_qualification_and_exclude_assigned_people(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();

        $match = $this->candidate($craft, $qualification, ['first_name' => 'Anna', 'last_name' => 'Match']);

        $otherQualification = ShiftQualification::factory()->create();
        $this->candidate($craft, $otherQualification, ['first_name' => 'Bernd', 'last_name' => 'OtherQualification']);

        $otherCraft = Craft::factory()->create();
        $this->candidate($otherCraft, $qualification, ['first_name' => 'Clara', 'last_name' => 'OtherCraft']);

        $alreadyAssigned = $this->candidate($craft, $qualification, ['first_name' => 'Dirk', 'last_name' => 'Assigned']);
        $shift->users()->attach($alreadyAssigned->id, ['shift_qualification_id' => $qualification->id]);

        $this->candidate($craft, $qualification, ['first_name' => 'Emil', 'last_name' => 'NoShifts', 'can_work_shifts' => false]);

        $response = $this->getJson(route('shift.replacement-candidates', [
            'shift' => $shift->id,
            'shift_worker_id' => $declinedPivot->id,
        ]))->assertOk();

        $ids = collect($response->json('candidates'))->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
        $this->assertSame('Bin krank', $response->json('slot.confirmation_comment'));
        $this->assertSame($qualification->name, $response->json('slot.qualification_name'));
        $this->assertSame($qualification->name, $response->json('candidates.0.qualification_name'));
    }

    #[Test]
    public function candidates_exclude_people_on_vacation_or_with_overlapping_assignments(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();

        // Ganztags Urlaub → raus
        $onVacation = $this->candidate($craft, $qualification, ['first_name' => 'Frida', 'last_name' => 'Vacation']);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $onVacation->id,
            'date' => self::DATE,
            'full_day' => true,
            'type' => VacationType::OFF_WORK->value,
        ]);

        // Nicht verfügbar 12–13 Uhr, Slot 10–14 → überlappt → raus
        $partlyUnavailable = $this->candidate($craft, $qualification, ['first_name' => 'Gero', 'last_name' => 'Unavailable']);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $partlyUnavailable->id,
            'date' => self::DATE,
            'full_day' => false,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'type' => VacationType::NOT_AVAILABLE->value,
        ]);

        // Nicht verfügbar 15–16 Uhr → überlappt nicht → bleibt
        $unavailableLater = $this->candidate($craft, $qualification, ['first_name' => 'Hanna', 'last_name' => 'Later']);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $unavailableLater->id,
            'date' => self::DATE,
            'full_day' => false,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'type' => VacationType::NOT_AVAILABLE->value,
        ]);

        // Andere Schicht 13–16 Uhr am selben Tag → überlappt → raus
        $overlapping = $this->candidate($craft, $qualification, ['first_name' => 'Ida', 'last_name' => 'Overlap']);
        $this->assignToOtherShift($overlapping, $qualification, self::DATE, '13:00:00', self::DATE, '16:00:00');

        // Andere Schicht 06–09 Uhr → frei, aber Hinweis
        $earlyShift = $this->candidate($craft, $qualification, ['first_name' => 'Jonas', 'last_name' => 'Early']);
        $this->assignToOtherShift($earlyShift, $qualification, self::DATE, '06:00:00', self::DATE, '09:00:00');

        // Nachtschicht vom Vortag 22–11 Uhr (über Mitternacht) → überlappt → raus
        $nightShift = $this->candidate($craft, $qualification, ['first_name' => 'Kai', 'last_name' => 'Night']);
        $this->assignToOtherShift($nightShift, $qualification, '2026-06-07', '22:00:00', self::DATE, '11:00:00');

        // Individuelle Pivot-Zeit 15–18 Uhr auf einer Schicht 10–18 Uhr → effektiv frei
        $individualTimes = $this->candidate($craft, $qualification, ['first_name' => 'Lena', 'last_name' => 'Pivot']);
        $this->assignToOtherShift(
            $individualTimes,
            $qualification,
            self::DATE,
            '10:00:00',
            self::DATE,
            '18:00:00',
            ['start_time' => '15:00:00', 'end_time' => '18:00:00']
        );

        $response = $this->getJson(route('shift.replacement-candidates', [
            'shift' => $shift->id,
            'shift_worker_id' => $declinedPivot->id,
        ]))->assertOk();

        $candidates = collect($response->json('candidates'))->keyBy('id');

        $this->assertEqualsCanonicalizing(
            [$unavailableLater->id, $earlyShift->id, $individualTimes->id],
            $candidates->keys()->all()
        );
        $this->assertNotEmpty($candidates[$earlyShift->id]['hints']);
        $this->assertStringContainsString('06:00', $candidates[$earlyShift->id]['hints'][0]);
    }

    #[Test]
    public function candidates_are_sorted_by_balance_ascending_then_name_with_unknown_last(): void
    {
        $this->actingAsUserWith([PermissionEnum::SHIFT_PLANNER->value, PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value]);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();

        $plus = $this->candidate($craft, $qualification, ['first_name' => 'Zoe', 'last_name' => 'Plus', 'work_time_balance' => 120]);
        $minus = $this->candidate($craft, $qualification, ['first_name' => 'Yara', 'last_name' => 'Minus', 'work_time_balance' => -60]);
        $zeroB = $this->candidate($craft, $qualification, ['first_name' => 'Ben', 'last_name' => 'Zero', 'work_time_balance' => 0]);
        $zeroA = $this->candidate($craft, $qualification, ['first_name' => 'Anton', 'last_name' => 'Zero', 'work_time_balance' => 0]);

        $freelancer = Freelancer::factory()->create(['first_name' => 'Aaron', 'last_name' => 'Free', 'can_work_shifts' => true]);
        $freelancer->assignedCrafts()->attach($craft->id);
        $freelancer->shiftQualifications()->attach($qualification->id, ['craft_id' => $craft->id]);

        $response = $this->getJson(route('shift.replacement-candidates', [
            'shift' => $shift->id,
            'shift_worker_id' => $declinedPivot->id,
        ]))->assertOk();

        $candidates = $response->json('candidates');

        $this->assertSame(
            [
                ['user', $minus->id],
                ['user', $zeroA->id],
                ['user', $zeroB->id],
                ['user', $plus->id],
                ['freelancer', $freelancer->id],
            ],
            array_map(fn (array $c) => [$c['type'], $c['id']], $candidates)
        );

        $this->assertSame(-60, $candidates[0]['balance_minutes']);
        $this->assertNotNull($candidates[0]['balance_label']);
        $this->assertNull($candidates[4]['balance_minutes']);
    }

    #[Test]
    public function balance_is_hidden_without_hours_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();
        $this->candidate($craft, $qualification, ['work_time_balance' => 120]);

        $response = $this->getJson(route('shift.replacement-candidates', [
            'shift' => $shift->id,
            'shift_worker_id' => $declinedPivot->id,
        ]))->assertOk();

        $this->assertNull($response->json('candidates.0.balance_minutes'));
        $this->assertNull($response->json('candidates.0.balance_label'));
    }

    #[Test]
    public function candidate_query_count_does_not_grow_with_number_of_candidates(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();

        $service = app(ShiftReplacementService::class);

        // Baseline mit denselben Nebendaten (Abwesenheit + Fremdschicht), damit die Eager-Loads
        // der Fremdschichten (shifts, crafts) in beiden Läufen vorkommen.
        for ($i = 0; $i < 2; $i++) {
            $base = $this->candidate($craft, $qualification);
            Vacation::factory()->create([
                'vacationer_type' => User::class,
                'vacationer_id' => $base->id,
                'date' => self::DATE,
                'full_day' => false,
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'type' => VacationType::NOT_AVAILABLE->value,
            ]);
            $this->assignToOtherShift($base, $qualification, self::DATE, '06:00:00', self::DATE, '08:00:00');
        }
        $queriesWithTwo = $this->countQueries(fn () => $service->candidatesFor($shift->fresh(), $declinedPivot->fresh()));

        for ($i = 0; $i < 8; $i++) {
            $extra = $this->candidate($craft, $qualification);
            // Jede weitere Person bringt eigene Abwesenheiten/Schichten/Tagesdienste mit,
            // die einzeln geladen ein N+1 wären.
            Vacation::factory()->create([
                'vacationer_type' => User::class,
                'vacationer_id' => $extra->id,
                'date' => self::DATE,
                'full_day' => false,
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'type' => VacationType::NOT_AVAILABLE->value,
            ]);
            $this->assignToOtherShift($extra, $qualification, self::DATE, '06:00:00', self::DATE, '08:00:00');
        }
        $queriesWithTen = $this->countQueries(fn () => $service->candidatesFor($shift->fresh(), $declinedPivot->fresh()));

        $this->assertSame($queriesWithTwo, $queriesWithTen);
        $this->assertCount(10, $service->candidatesFor($shift->fresh(), $declinedPivot->fresh())['candidates']);
    }

    #[Test]
    public function candidates_require_plan_shifts_permission(): void
    {
        $this->actingAs(User::factory()->create());
        [$shift, , , $declinedPivot] = $this->declinedShift();

        $this->getJson(route('shift.replacement-candidates', [
            'shift' => $shift->id,
            'shift_worker_id' => $declinedPivot->id,
        ]))->assertForbidden();
    }

    /* ---------------- Ersetzen ---------------- */

    #[Test]
    public function replacing_swaps_the_pivot_and_notifies_both_people_on_a_committed_shift(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot, $declinedUser] = $this->declinedShift(committed: true);
        $replacement = $this->candidate($craft, $qualification, ['first_name' => 'Rita', 'last_name' => 'Replacement']);

        $response = $this->postJson(route('shift.replace-worker', $shift), [
            'shift_worker_id' => $declinedPivot->id,
            'replacement_type' => 'user',
            'replacement_id' => $replacement->id,
        ])->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertDatabaseMissing('shift_workers', ['id' => $declinedPivot->id]);

        $newPivot = ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', User::class)
            ->where('employable_id', $replacement->id)
            ->first();

        $this->assertNotNull($newPivot);
        $this->assertSame($qualification->id, (int) $newPivot->shift_qualification_id);
        $this->assertNull($newPivot->confirmation_status);
        $this->assertSame((int) $newPivot->id, (int) $response->json('shift_worker_id'));

        $workerIds = collect($response->json('workers'))->pluck('id')->all();
        $this->assertContains($replacement->id, $workerIds);
        $this->assertNotContains($declinedUser->id, $workerIds);

        // Bestehende Notification-Typen: „entfernt" an die abgesagte, „zugewiesen" an die neue Person
        Notification::assertSentTo($declinedUser, ShiftNotification::class);
        Notification::assertSentTo($replacement, ShiftNotification::class);

        $this->assertTrue(
            Activity::query()
                ->where('log_name', 'shift')
                ->where('subject_id', $shift->id)
                ->where('event', 'replaced')
                ->exists()
        );
    }

    #[Test]
    public function replacing_is_allowed_even_if_the_replacement_has_a_conflict(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();
        $replacement = $this->candidate($craft, $qualification);
        // Überschneidende Schicht → im Kandidaten-Vorschlag nicht enthalten, Ersetzen (z. B. per Drag&Drop-Wissen) bleibt erlaubt
        $this->assignToOtherShift($replacement, $qualification, self::DATE, '12:00:00', self::DATE, '16:00:00');

        $this->postJson(route('shift.replace-worker', $shift), [
            'shift_worker_id' => $declinedPivot->id,
            'replacement_type' => 'user',
            'replacement_id' => $replacement->id,
        ])->assertOk();

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $replacement->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function replacing_rejects_a_person_who_is_already_assigned(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();
        $assigned = $this->candidate($craft, $qualification);
        $shift->users()->attach($assigned->id, ['shift_qualification_id' => $qualification->id]);

        $this->postJson(route('shift.replace-worker', $shift), [
            'shift_worker_id' => $declinedPivot->id,
            'replacement_type' => 'user',
            'replacement_id' => $assigned->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['replacement_id']);

        $this->assertDatabaseHas('shift_workers', ['id' => $declinedPivot->id, 'deleted_at' => null]);
    }

    #[Test]
    public function a_second_replacement_of_the_same_declined_assignment_is_rejected_with_409(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();
        $first = $this->candidate($craft, $qualification, ['first_name' => 'Erste', 'last_name' => 'Ersatz']);
        $second = $this->candidate($craft, $qualification, ['first_name' => 'Zweite', 'last_name' => 'Ersatz']);

        $service = app(ShiftReplacementService::class);
        $replace = fn (User $replacement) => $service->replace(
            $shift,
            $declinedPivot,
            $replacement,
            $qualification->id,
            (string) $craft->abbreviation,
            app(NotificationService::class),
            app(VacationConflictService::class),
            app(AvailabilityConflictService::class),
            app(ChangeService::class)
        );

        $replace($first);

        // Zweiter Aufruf mit demselben (inzwischen entfernten) Pivot – wie ein paralleler zweiter Klick,
        // der die Vorabprüfung des Controllers noch passiert hat: die Transaktion lädt den Satz gesperrt
        // neu, findet ihn nicht mehr und bricht mit 409 ab statt eine zweite Ersatzperson einzutragen.
        try {
            $replace($second);
            $this->fail('Zweites Ersetzen derselben Zuweisung muss mit 409 abbrechen.');
        } catch (ConflictHttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }

        $active = ShiftWorker::withoutTrashed()->where('shift_id', $shift->id)->get();
        $this->assertCount(1, $active);
        $this->assertSame($first->id, (int) $active->first()->employable_id);
        $this->assertDatabaseMissing('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $second->id,
            'employable_type' => User::class,
            'deleted_at' => null,
        ]);

        // Über HTTP kennt der Controller den entfernten Satz gar nicht mehr (404) – keine zweite Ersatzperson
        $this->postJson(route('shift.replace-worker', $shift), [
            'shift_worker_id' => $declinedPivot->id,
            'replacement_type' => 'user',
            'replacement_id' => $second->id,
        ])->assertNotFound();
        $this->assertSame(1, ShiftWorker::withoutTrashed()->where('shift_id', $shift->id)->count());
    }

    #[Test]
    public function replacing_requires_plan_shifts_permission(): void
    {
        $this->actingAs(User::factory()->create());
        [$shift, $craft, $qualification, $declinedPivot] = $this->declinedShift();
        $replacement = $this->candidate($craft, $qualification);

        $this->postJson(route('shift.replace-worker', $shift), [
            'shift_worker_id' => $declinedPivot->id,
            'replacement_type' => 'user',
            'replacement_id' => $replacement->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('shift_workers', ['id' => $declinedPivot->id, 'deleted_at' => null]);
    }

    #[Test]
    public function replacing_returns_404_for_a_pivot_of_another_shift(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        [, $craft, $qualification, $declinedPivot] = $this->declinedShift();
        $otherShift = Shift::factory()->create(['craft_id' => $craft->id]);
        $replacement = $this->candidate($craft, $qualification);

        $this->postJson(route('shift.replace-worker', $otherShift), [
            'shift_worker_id' => $declinedPivot->id,
            'replacement_type' => 'user',
            'replacement_id' => $replacement->id,
        ])->assertNotFound();
    }

    /* ---------------- Helfer ---------------- */

    /**
     * @return array{0: Shift, 1: Craft, 2: ShiftQualification, 3: ShiftWorker, 4: User}
     */
    private function declinedShift(bool $committed = false): array
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $qualification = ShiftQualification::factory()->create();

        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => self::DATE,
            'end_date' => self::DATE,
            'start' => '10:00:00',
            'end' => '14:00:00',
            'is_committed' => $committed,
            'committing_user_id' => $committed ? User::factory()->create()->id : null,
        ]);

        $declinedUser = $this->candidate($craft, $qualification, ['first_name' => 'Doris', 'last_name' => 'Declined']);
        $shift->users()->attach($declinedUser->id, [
            'shift_qualification_id' => $qualification->id,
            'craft_abbreviation' => $craft->abbreviation,
            'start_date' => self::DATE,
            'end_date' => self::DATE,
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
            'confirmation_status' => ShiftWorker::CONFIRMATION_DECLINED,
            'confirmation_comment' => 'Bin krank',
            'confirmation_at' => now(),
            'confirmation_by_user_id' => $declinedUser->id,
        ]);

        $pivot = ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', User::class)
            ->where('employable_id', $declinedUser->id)
            ->firstOrFail();

        return [$shift->fresh(), $craft, $qualification, $pivot, $declinedUser];
    }

    /**
     * Person des Gewerks mit der Funktion (craftables + shift_qualifiables).
     *
     * @param array<string, mixed> $attributes
     */
    private function candidate(Craft $craft, ShiftQualification $qualification, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['can_work_shifts' => true], $attributes));
        $user->assignedCrafts()->attach($craft->id);
        $user->shiftQualifications()->attach($qualification->id, ['craft_id' => $craft->id]);

        return $user;
    }

    /**
     * @param array<string, mixed> $pivotOverrides
     */
    private function assignToOtherShift(
        User $user,
        ShiftQualification $qualification,
        string $startDate,
        string $start,
        string $endDate,
        string $end,
        array $pivotOverrides = []
    ): Shift {
        $otherShift = Shift::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start' => $start,
            'end' => $end,
        ]);

        $otherShift->users()->attach($user->id, array_merge([
            'shift_qualification_id' => $qualification->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $start,
            'end_time' => $end,
        ], $pivotOverrides));

        return $otherShift;
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
