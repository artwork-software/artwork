<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftPlanRequestChange;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Welle-2-Regression: Scope-Gruppierung (B7), Status-Guards (B11),
 * _initial-Rollback nur aus eigenem Request (B10), event_start_day-Sync (B14c),
 * individuelle Zeitänderungen im Verlauf (B13), Event-Restore-Kaskade (B9).
 */
final class ShiftModuleWave2RegressionTest extends FeatureTestCase
{
    // ---------- B7: Scope-Gruppierung ----------

    #[Test]
    public function uuid_filter_is_not_bypassed_by_date_scope(): void
    {
        $uuid = (string) Str::uuid();
        $inSeries = Shift::factory()->create([
            'shift_uuid' => $uuid,
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-06',
        ]);
        // Fremde Schicht im selben Zeitraum — darf NICHT mitkommen
        Shift::factory()->create([
            'shift_uuid' => (string) Str::uuid(),
            'start_date' => '2026-07-07',
            'end_date' => '2026-07-07',
        ]);

        $result = Shift::allByUuid($uuid)
            ->eventStartDayAndEventEndDayBetween(
                \Carbon\Carbon::parse('2026-07-01'),
                \Carbon\Carbon::parse('2026-07-31')
            )
            ->get();

        $this->assertCount(1, $result);
        $this->assertSame($inSeries->id, $result->first()->id);
    }

    // ---------- B11: Status-Guards ----------

    #[Test]
    public function approved_request_cannot_be_rejected_afterwards(): void
    {
        $admin = $this->actingAsAdmin();
        $request = $this->createRequest('approved');

        $this->post(route('shift-plan-requests.reject', $request), [])
            ->assertSessionHas('error');

        $this->assertSame('approved', $request->fresh()->status);
    }

    #[Test]
    public function rejected_request_cannot_be_accepted_afterwards(): void
    {
        $this->actingAsAdmin();
        $request = $this->createRequest('rejected');

        $this->post(route('shift-plan-requests.accept', $request))
            ->assertSessionHas('error');

        $this->assertSame('rejected', $request->fresh()->status);
    }

    #[Test]
    public function processed_request_cannot_be_destroyed(): void
    {
        $this->actingAsAdmin();
        $request = $this->createRequest('approved');

        $this->delete(route('shift-plan-requests.destroy', $request))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('shift_plan_requests', ['id' => $request->id]);
    }

    // ---------- B10: _initial nur aus eigenem Request ----------

    #[Test]
    public function reject_rolls_back_to_initial_of_current_request_not_an_older_one(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $oldRequest = $this->createRequest('rejected', $craft);
        $currentRequest = $this->createRequest('pending', $craft);

        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start' => '12:00:00',
            'end' => '20:00:00',
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'in_workflow' => true,
            'current_request_id' => $currentRequest->id,
        ]);

        // Älterer Change aus dem ERSTEN Request mit anderem _initial (08:00)
        ShiftPlanRequestChange::create([
            'shift_plan_request_id' => $oldRequest->id,
            'subject_type' => Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'updated',
            'field_changes' => ['_initial' => ['start' => '08:00', 'end' => '16:00']],
            'changed_at' => now()->subDays(10),
        ]);
        // Change aus dem AKTUELLEN Request mit _initial = 10:00
        ShiftPlanRequestChange::create([
            'shift_plan_request_id' => $currentRequest->id,
            'subject_type' => Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'updated',
            'field_changes' => ['_initial' => ['start' => '10:00', 'end' => '18:00']],
            'changed_at' => now()->subDay(),
        ]);

        $this->post(route('shift-plan-requests.reject', $currentRequest), [])
            ->assertSessionHas('success');

        $shift->refresh();
        // Muss auf das _initial des AKTUELLEN Requests zurück (10:00), nicht des alten (08:00)
        $this->assertSame('10:00', $shift->start);
        $this->assertSame('18:00', $shift->end);
    }

    // ---------- B14c: event_start_day-Sync ----------

    #[Test]
    public function eventless_shift_gets_event_days_synced_from_dates(): void
    {
        $shift = Shift::factory()->create([
            'event_id' => null,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-11',
            'event_start_day' => null,
            'event_end_day' => null,
        ]);

        $this->assertSame('2026-07-10', $shift->fresh()->event_start_day);
        $this->assertSame('2026-07-11', $shift->fresh()->event_end_day);

        $shift->update(['start_date' => '2026-07-20', 'end_date' => '2026-07-20']);

        $this->assertSame('2026-07-20', $shift->fresh()->event_start_day);
        $this->assertSame('2026-07-20', $shift->fresh()->event_end_day);
    }

    // ---------- B13: individuelle Zeitänderung im Verlauf ----------

    #[Test]
    public function individual_time_change_on_committed_shift_is_recorded(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'is_committed' => true,
        ]);
        $qualification = ShiftQualification::factory()->create();
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => $qualification->id,
        ]);
        // MorphPivot hat $incrementing = false — create() befüllt die id nicht,
        // daher frisch laden.
        $pivot = ShiftWorker::where('shift_id', $shift->id)->firstOrFail();

        $this->postJson(route('shifts.updateIndividualShiftTime'), [
            'shiftPivotId' => $pivot->id,
            'entity' => ['type' => 'user', 'id' => $user->id],
            'start_time' => '10:00',
            'end_time' => '15:00',
        ])->assertSuccessful();

        $this->assertDatabaseHas('committed_shift_changes', [
            'shift_id' => $shift->id,
            'affected_user_id' => $user->id,
            'affected_user_type' => 'user',
        ]);
    }

    // ---------- B9: Event-Restore stellt Schichten wieder her ----------

    #[Test]
    public function restoring_event_from_trash_restores_its_shifts(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
        ]);

        // Soft-Delete über den regulären Pfad (Event + Schichten in den Papierkorb)
        $this->delete(route('events.delete', $event));
        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);

        $this->patch(route('events.restore', ['id' => $event->id]));

        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'deleted_at' => null]);
    }

    private function createRequest(string $status, ?Craft $craft = null): ShiftPlanRequest
    {
        return ShiftPlanRequest::create([
            'craft_id' => ($craft ?? Craft::factory()->create())->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => $status,
            'requested_by_user_id' => $this->adminUser()->id,
            'requested_at' => now(),
        ]);
    }
}
