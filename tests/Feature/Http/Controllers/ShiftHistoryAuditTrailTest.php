<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

/**
 * Der Schichtverlauf soll ein vollständiger Audit-Trail sein: Festschreibung
 * (Sammel-Eintrag), Bedarfs-Änderungen, individuelle Pivot-Zeiten und die
 * "Betroffen"-Namen bei Kaskaden-Löschung müssen im shift-Activity-Log landen.
 */
final class ShiftHistoryAuditTrailTest extends FeatureTestCase
{
    private function makeShift(array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ], $attributes));
    }

    /**
     * Setzt created_at der Schicht in die Vergangenheit, damit der Observer sie
     * nicht als "gerade erst erstellt" behandelt (Spam-Schutz bei Initial-Slots).
     */
    private function ageShift(Shift $shift): void
    {
        Shift::query()->whereKey($shift->id)->update(['created_at' => now()->subDay()]);
    }

    private function shiftActivities(string $event): \Illuminate\Support\Collection
    {
        return Activity::query()
            ->where('log_name', 'shift')
            ->where('event', $event)
            ->get();
    }

    #[Test]
    public function committing_a_calendar_week_writes_one_summary_entry(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $shiftA = $this->makeShift(['craft_id' => $craft->id, 'start_date' => '2026-05-06', 'end_date' => '2026-05-06']);
        $shiftB = $this->makeShift(['craft_id' => $craft->id, 'start_date' => '2026-05-08', 'end_date' => '2026-05-08']);

        app(ShiftService::class)->commitShiftsByDate(
            Carbon::parse('2026-05-04'),
            Carbon::parse('2026-05-10'),
            $craft->id,
            19,
            2026
        );

        $this->assertTrue($shiftA->fresh()->is_committed);
        $this->assertTrue($shiftB->fresh()->is_committed);

        // Genau EIN Sammel-Eintrag, kein Eintrag pro Schicht.
        $summaries = $this->shiftActivities('committed_bulk');
        $this->assertCount(1, $summaries);

        $summary = $summaries->first();
        $this->assertNull($summary->subject_id);
        $this->assertSame('commit', $summary->properties['context']);
        $this->assertSame(19, $summary->properties['commit_summary']['week']);
        $this->assertSame(2, $summary->properties['commit_summary']['count']);
        $this->assertSame('2026-05-06', $summary->properties['commit_summary']['start_date']);
        $this->assertSame('2026-05-08', $summary->properties['commit_summary']['end_date']);
        $this->assertContains($shiftA->id, $summary->properties['shift_ids']);
    }

    #[Test]
    public function history_endpoint_returns_commit_summary_and_respects_craft_filter(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $otherCraft = Craft::factory()->create();

        $this->makeShift(['craft_id' => $craft->id]);

        app(ShiftService::class)->commitShiftsByDate(
            Carbon::parse('2026-05-04'),
            Carbon::parse('2026-05-10'),
            $craft->id,
            19,
            2026
        );

        // Ohne Craft-Filter: Sammel-Eintrag enthalten.
        $response = $this->getJson(route('shift.history.index', [
            'craftId' => 0,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));
        $response->assertOk();
        $events = collect($response->json('logs.data'))->pluck('event');
        $this->assertContains('committed_bulk', $events);

        // Auch mit Schichttag-Sortierung (JOIN + COALESCE) darf nichts brechen.
        $this->getJson(route('shift.history.index', [
            'craftId' => 0,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'sort' => 'shift_day',
        ]))->assertOk();

        // Fremdes Gewerk: Sammel-Eintrag wird herausgefiltert.
        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $otherCraft->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));
        $response->assertOk();
        $events = collect($response->json('logs.data'))->pluck('event');
        $this->assertNotContains('committed_bulk', $events);
    }

    #[Test]
    public function single_commit_toggle_is_logged(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        $this->postJson(route('shift.change.commit.status', $shift), ['commit' => true])
            ->assertSuccessful();

        $this->assertTrue($shift->fresh()->is_committed);

        $entries = $this->shiftActivities('committed');
        $this->assertCount(1, $entries);
        $this->assertSame($shift->id, (int) $entries->first()->subject_id);
        $this->assertSame(
            'Shift was committed',
            $entries->first()->properties['translation_key']
        );

        // Aufheben wird ebenfalls geloggt.
        $this->postJson(route('shift.change.commit.status', $shift), ['commit' => false])
            ->assertSuccessful();

        $this->assertCount(1, $this->shiftActivities('uncommitted'));
    }

    #[Test]
    public function changing_required_workers_is_logged_but_initial_slots_are_not(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();
        $qualification = ShiftQualification::factory()->create(['name' => 'Techniker']);
        $service = app(ShiftsQualificationsService::class);

        // Initialer Schichtplatz direkt nach Schicht-Erstellung: KEIN Verlaufseintrag.
        $service->createShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);

        $this->assertSame(
            0,
            Activity::query()
                ->where('log_name', 'shift')
                ->where('properties->translation_key', 'Shift slot {0} added with {1} required workers')
                ->count()
        );

        // Bestehende (ältere) Schicht: Bedarfs-Änderung wird geloggt.
        $this->ageShift($shift);

        $service->updateShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'value' => 5,
        ]);

        $entry = Activity::query()
            ->where('log_name', 'shift')
            ->where('properties->translation_key', 'Required workers for {0} changed from {1} to {2}')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($shift->id, (int) $entry->subject_id);
        $this->assertSame(['Techniker', 2, 5], $entry->properties['translation_key_placeholder_values']);

        // Nachträglich hinzugefügter Slot auf bestehender Schicht wird geloggt.
        $secondQualification = ShiftQualification::factory()->create(['name' => 'Meister']);
        $service->updateShiftsQualificationForShift($shift->id, [
            'shift_qualification_id' => $secondQualification->id,
            'value' => 1,
        ]);

        $this->assertSame(
            1,
            Activity::query()
                ->where('log_name', 'shift')
                ->where('properties->translation_key', 'Shift slot {0} added with {1} required workers')
                ->count()
        );
    }

    #[Test]
    public function individual_working_time_change_is_logged_for_normal_shifts(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();
        $user = User::factory()->create();

        ShiftWorker::query()->create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);
        $pivot = ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_id', $user->id)
            ->firstOrFail();

        app(ShiftWorkerService::class)->logIndividualPivotChange(
            $pivot,
            'individual_time',
            '09:00 - 17:00',
            '10:00 - 18:00'
        );

        $entry = Activity::query()
            ->where('log_name', 'shift')
            ->where('properties->translation_key', 'Individual working time for {0} changed from {1} to {2}')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($shift->id, (int) $entry->subject_id);
        $this->assertNotNull($entry->properties['shift_snapshot'] ?? null);
    }

    #[Test]
    public function cascade_delete_keeps_affected_worker_names_in_log_entry(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();
        $user = User::factory()->create(['first_name' => 'Zaphod', 'last_name' => 'Beeblebrox']);

        ShiftWorker::query()->create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        // Kaskaden-Pfad (Event-/Projekt-Löschung): Pivots werden VOR der Schicht gelöscht.
        app(ShiftService::class)->delete(
            $shift->fresh(),
            app(ShiftsQualificationsService::class),
            app(ShiftUserService::class),
            app(ShiftFreelancerService::class),
            app(ShiftServiceProviderService::class)
        );

        $entry = Activity::query()
            ->where('log_name', 'shift')
            ->where('event', 'deleted')
            ->where('subject_id', $shift->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertContains('Zaphod Beeblebrox', $entry->properties['affected_workers'] ?? []);
    }

    #[Test]
    public function withdrawing_a_shift_plan_request_logs_release_per_shift(): void
    {
        $admin = $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $request = ShiftPlanRequest::query()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $admin->id,
            'requested_at' => now(),
        ]);

        $shift = $this->makeShift([
            'craft_id' => $craft->id,
            'in_workflow' => true,
            'current_request_id' => $request->id,
        ]);

        $this->deleteJson(route('shift-plan-requests.destroy', $request))
            ->assertRedirect();

        $shift->refresh();
        $this->assertFalse((bool) $shift->in_workflow);
        $this->assertNull($shift->current_request_id);

        $entries = $this->shiftActivities('workflow_withdrawn');
        $this->assertCount(1, $entries);
        $this->assertSame($shift->id, (int) $entries->first()->subject_id);
    }
}
