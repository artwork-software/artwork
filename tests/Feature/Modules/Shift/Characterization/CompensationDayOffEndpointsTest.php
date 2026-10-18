<?php

namespace Tests\Feature\Modules\Shift\Characterization;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten der Ersatzfreitag-Endpunkte fest (compensation-day-offs.grant/check/revoke/
 * store-manual/open/delete). Gate ist allein "can plan shifts" (Routengruppe); Gewährung prüft
 * bestehende Schichten am Tag und kann sie auf Wunsch entfernen, Löschen verlangt eine Begründung
 * und schreibt sie ins Activity-Log.
 */
final class CompensationDayOffEndpointsTest extends FeatureTestCase
{
    use CreatesShiftRuleFixtures;

    private function actingAsPlanner(): User
    {
        return $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
    }

    private function openDay(User $user, float $value = 1.0, array $attributes = []): CompensationDayOff
    {
        return CompensationDayOff::create(array_merge([
            'user_id' => $user->id,
            'value' => $value,
            'deadline' => Carbon::now()->addDays(30)->toDateString(),
        ], $attributes));
    }

    private function grantedDay(User $user, float $value = 1.0): CompensationDayOff
    {
        return $this->openDay($user, $value, [
            'granted_date' => Carbon::now()->addDays(3)->toDateString(),
            'granted_at' => Carbon::now(),
        ]);
    }

    // --- grant -------------------------------------------------------------------------------

    #[Test]
    public function grant_books_the_day_for_the_acting_planner(): void
    {
        $planner = $this->actingAsPlanner();
        $day = $this->openDay(User::factory()->create());
        $date = Carbon::now()->addDays(4)->toDateString();

        $this->post(route('compensation-day-offs.grant', $day), ['granted_date' => $date])
            ->assertRedirect()
            ->assertSessionHas('success');

        $day->refresh();
        $this->assertTrue($day->isGranted());
        $this->assertSame($date, $day->granted_date->toDateString());
        $this->assertSame($planner->id, (int) $day->granted_by);
        $this->assertNull($day->half_day_period);
    }

    #[Test]
    public function grant_of_an_already_granted_day_returns_json_422(): void
    {
        $this->actingAsPlanner();
        $day = $this->grantedDay(User::factory()->create());
        $originalDate = $day->granted_date->toDateString();

        $this->post(route('compensation-day-offs.grant', $day), [
            'granted_date' => Carbon::now()->addDays(10)->toDateString(),
        ])->assertStatus(422)->assertJsonPath('error', 'Compensation day already granted.');

        $this->assertSame($originalDate, $day->refresh()->granted_date->toDateString());
    }

    #[Test]
    public function grant_with_check_only_reports_shifts_on_that_day_without_granting(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();
        $date = Carbon::now()->addDays(6)->startOfDay();
        $this->shiftFor($worker, $date);
        $day = $this->openDay($worker);

        $this->post(route('compensation-day-offs.grant', $day), [
            'granted_date' => $date->toDateString(),
            'check_only' => true,
        ])->assertOk()->assertExactJson(['has_shifts' => true, 'shift_count' => 1]);

        $this->assertFalse($day->refresh()->isGranted());
    }

    #[Test]
    public function grant_with_remove_shifts_removes_the_worker_from_shifts_on_that_day(): void
    {
        $this->actingAsAdmin();
        $worker = User::factory()->create();
        $date = Carbon::now()->addDays(6)->startOfDay();
        $shift = $this->shiftFor($worker, $date);
        $day = $this->openDay($worker);

        $this->post(route('compensation-day-offs.grant', $day), [
            'granted_date' => $date->toDateString(),
            'remove_shifts' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($day->refresh()->isGranted());
        $this->assertFalse(ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', User::class)
            ->where('employable_id', $worker->id)
            ->exists());
    }

    #[Test]
    public function grant_with_remove_shifts_respects_craft_planning_rights(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();
        $date = Carbon::now()->addDays(6)->startOfDay();
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $shift = $this->shiftFor($worker, $date, '08:00:00', '16:00:00', ['craft_id' => $foreignCraft->id]);
        $day = $this->openDay($worker);

        $this->postJson(route('compensation-day-offs.grant', $day), [
            'granted_date' => $date->toDateString(),
            'remove_shifts' => true,
        ])->assertForbidden();

        $this->assertFalse($day->refresh()->isGranted());
        $this->assertTrue(ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', User::class)
            ->where('employable_id', $worker->id)
            ->exists(), 'Planer ohne Planungsrecht für das Gewerk hat eine Person aus der Schicht entfernt.');
    }

    #[Test]
    public function grant_of_a_half_day_as_both_without_a_second_open_half_fails_validation(): void
    {
        $this->actingAsPlanner();
        $day = $this->openDay(User::factory()->create(), 0.5);

        $this->post(route('compensation-day-offs.grant', $day), [
            'granted_date' => Carbon::now()->addDays(4)->toDateString(),
            'half_day_period' => 'both',
        ])->assertSessionHasErrors('half_day_period');

        $this->assertFalse($day->refresh()->isGranted());
    }

    #[Test]
    public function grant_of_a_half_day_as_both_pairs_two_open_halves_into_morning_and_afternoon(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();
        $first = $this->openDay($worker, 0.5);
        $second = $this->openDay($worker, 0.5);
        $date = Carbon::now()->addDays(4)->toDateString();

        $this->post(route('compensation-day-offs.grant', $first), [
            'granted_date' => $date,
            'half_day_period' => 'both',
        ])->assertRedirect()->assertSessionHas('success');

        $first->refresh();
        $second->refresh();
        $this->assertSame('morning', $first->half_day_period);
        $this->assertSame('afternoon', $second->half_day_period);
        $this->assertSame($date, $second->granted_date->toDateString());
    }

    #[Test]
    public function grant_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $day = $this->openDay(User::factory()->create());

        $this->post(route('compensation-day-offs.grant', $day), [
            'granted_date' => Carbon::now()->addDays(4)->toDateString(),
        ])->assertForbidden();

        $this->assertFalse($day->refresh()->isGranted());
    }

    // --- check -------------------------------------------------------------------------------

    #[Test]
    public function check_reports_shift_count_and_no_special_day_for_a_regular_day(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();
        $date = Carbon::now()->addDays(8)->startOfDay();
        $this->shiftFor($worker, $date);
        $day = $this->openDay($worker);

        $this->postJson(route('compensation-day-offs.check', $day), ['granted_date' => $date->toDateString()])
            ->assertOk()
            ->assertExactJson([
                'has_shifts' => true,
                'shift_count' => 1,
                'special_day' => false,
                'special_day_rule' => null,
            ]);
    }

    #[Test]
    public function check_requires_a_date(): void
    {
        $this->actingAsPlanner();
        $day = $this->openDay(User::factory()->create());

        $this->postJson(route('compensation-day-offs.check', $day), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('granted_date');
    }

    #[Test]
    public function check_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $day = $this->openDay(User::factory()->create());

        $this->postJson(route('compensation-day-offs.check', $day), [
            'granted_date' => Carbon::now()->toDateString(),
        ])->assertForbidden();
    }

    // --- revoke ------------------------------------------------------------------------------

    #[Test]
    public function revoke_clears_the_grant_of_a_granted_day(): void
    {
        $this->actingAsPlanner();
        $day = $this->grantedDay(User::factory()->create());

        $this->post(route('compensation-day-offs.revoke', $day))
            ->assertRedirect()
            ->assertSessionHas('success');

        $day->refresh();
        $this->assertFalse($day->isGranted());
        $this->assertNull($day->granted_date);
        $this->assertNull($day->granted_by);
    }

    #[Test]
    public function revoke_of_an_open_day_redirects_with_an_error_flash(): void
    {
        $this->actingAsPlanner();
        $day = $this->openDay(User::factory()->create());

        $this->post(route('compensation-day-offs.revoke', $day))
            ->assertRedirect()
            ->assertSessionHas('error', 'Compensation day is not granted.');
    }

    #[Test]
    public function revoke_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $day = $this->grantedDay(User::factory()->create());

        $this->post(route('compensation-day-offs.revoke', $day))->assertForbidden();

        $this->assertTrue($day->refresh()->isGranted());
    }

    // --- store-manual ------------------------------------------------------------------------

    #[Test]
    public function store_manual_creates_an_open_half_day_with_its_period(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();
        $deadline = Carbon::now()->addDays(20)->toDateString();

        $this->post(route('compensation-day-offs.store-manual'), [
            'user_id' => $worker->id,
            'value' => '0.5',
            'deadline' => $deadline,
            'reason' => 'Premiere',
            'half_day_period' => 'morning',
        ])->assertRedirect()->assertSessionHas('success');

        $day = CompensationDayOff::query()->where('user_id', $worker->id)->sole();
        $this->assertEquals(0.5, (float) $day->value);
        $this->assertSame('morning', $day->half_day_period);
        $this->assertNull($day->violation_id);
        $this->assertFalse($day->isGranted());
        $this->assertSame($deadline, $day->deadline->toDateString());
    }

    #[Test]
    public function store_manual_drops_the_period_for_a_whole_day(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();

        $this->post(route('compensation-day-offs.store-manual'), [
            'user_id' => $worker->id,
            'value' => '1.0',
            'deadline' => Carbon::now()->addDays(20)->toDateString(),
            'half_day_period' => 'afternoon',
        ])->assertRedirect();

        $this->assertNull(CompensationDayOff::query()->where('user_id', $worker->id)->sole()->half_day_period);
    }

    #[Test]
    public function store_manual_rejects_values_other_than_half_and_whole_days(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();

        $this->post(route('compensation-day-offs.store-manual'), [
            'user_id' => $worker->id,
            'value' => '2',
            'deadline' => Carbon::now()->addDays(20)->toDateString(),
        ])->assertSessionHasErrors('value');

        $this->assertSame(0, CompensationDayOff::query()->where('user_id', $worker->id)->count());
    }

    #[Test]
    public function store_manual_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $worker = User::factory()->create();

        $this->post(route('compensation-day-offs.store-manual'), [
            'user_id' => $worker->id,
            'value' => '1.0',
            'deadline' => Carbon::now()->addDays(20)->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, CompensationDayOff::query()->where('user_id', $worker->id)->count());
    }

    // --- open --------------------------------------------------------------------------------

    #[Test]
    public function open_lists_only_ungranted_days_of_the_user_ordered_by_deadline(): void
    {
        $this->actingAsPlanner();
        $worker = User::factory()->create();
        $later = $this->openDay($worker, 1.0, ['deadline' => Carbon::now()->addDays(40)->toDateString()]);
        $sooner = $this->openDay($worker, 1.0, ['deadline' => Carbon::now()->addDays(10)->toDateString()]);
        $this->grantedDay($worker);
        $this->openDay(User::factory()->create());

        $response = $this->getJson(route('compensation-day-offs.open', $worker))->assertOk();

        $this->assertSame([$sooner->id, $later->id], collect($response->json())->pluck('id')->all());
    }

    #[Test]
    public function open_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('compensation-day-offs.open', User::factory()->create()))->assertForbidden();
    }

    // --- delete ------------------------------------------------------------------------------

    #[Test]
    public function delete_removes_the_day_and_logs_the_reason(): void
    {
        $planner = $this->actingAsPlanner();
        $day = $this->openDay(User::factory()->create());

        $this->delete(route('compensation-day-offs.delete', $day), ['delete_reason' => 'Doppelt erfasst'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('compensation_day_offs', ['id' => $day->id]);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => CompensationDayOff::class,
            'subject_id' => $day->id,
            'event' => 'deleted_with_reason',
            'causer_id' => $planner->id,
        ]);
    }

    #[Test]
    public function delete_requires_a_reason(): void
    {
        $this->actingAsPlanner();
        $day = $this->openDay(User::factory()->create());

        $this->delete(route('compensation-day-offs.delete', $day), [])->assertSessionHasErrors('delete_reason');

        $this->assertDatabaseHas('compensation_day_offs', ['id' => $day->id]);
    }

    #[Test]
    public function delete_is_forbidden_without_the_planner_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $day = $this->openDay(User::factory()->create());

        $this->delete(route('compensation-day-offs.delete', $day), ['delete_reason' => 'x'])->assertForbidden();

        $this->assertDatabaseHas('compensation_day_offs', ['id' => $day->id]);
    }
}
