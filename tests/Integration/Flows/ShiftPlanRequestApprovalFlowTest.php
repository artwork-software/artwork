<?php

namespace Tests\Integration\Flows;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * End-to-end flow:
 *   1. Admin authenticated
 *   2. Craft + Event + Shift (within a calendar week)
 *   3. POST commit-shift-workflow-request.store → ShiftPlanRequest pending
 *   4. POST shift-plan-requests.accept → status flips to approved
 *   5. Shift is_committed flips to true
 *   Reject path also covered.
 */
final class ShiftPlanRequestApprovalFlowTest extends FeatureTestCase
{
    private function createCraftEventAndShift(): array
    {
        // 2026-05-06 liegt in ISO-Kalenderwoche 19 (Mo 2026-05-04 – So 2026-05-10),
        // passend zu week_number = 19 in den Requests unten.
        $craft = Craft::factory()->create();
        $event = Event::factory()->create([
            'start_time' => '2026-05-06 09:00:00',
            'end_time' => '2026-05-06 17:00:00',
        ]);
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);

        return [$craft, $event, $shift];
    }

    #[Test]
    public function admin_can_create_shift_plan_request_in_pending_state(): void
    {
        $this->actingAsAdmin();
        [$craft, , ] = $this->createCraftEventAndShift();

        $response = $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19, // ISO week for 2026-05-11
            'year' => 2026,
        ]);

        $response->assertRedirect();

        $request = ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('week_number', 19)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($request);
        $this->assertSame('pending', $request->status);
    }

    #[Test]
    public function admin_can_approve_pending_shift_plan_request(): void
    {
        $this->actingAsAdmin();
        [$craft, , $shift] = $this->createCraftEventAndShift();

        // 1. create request through HTTP
        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $request = ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('week_number', 19)
            ->where('year', 2026)
            ->firstOrFail();

        // 2. accept it
        $accept = $this->post(route('shift-plan-requests.accept', $request));
        $accept->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->reviewed_at);
        $this->assertNotNull($request->reviewed_by_user_id);
    }

    #[Test]
    public function admin_can_reject_pending_shift_plan_request(): void
    {
        $this->actingAsAdmin();
        [$craft, , ] = $this->createCraftEventAndShift();

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $request = ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->firstOrFail();

        $reject = $this->post(route('shift-plan-requests.reject', $request), [
            'review_comment' => 'Insufficient staffing',
        ]);
        $reject->assertRedirect();

        $request->refresh();
        $this->assertSame('rejected', $request->status);
    }

    #[Test]
    public function store_validates_required_craft_id(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('commit-shift-workflow-request.store'), [
            'week_number' => 19,
            'year' => 2026,
        ]);

        $response->assertSessionHasErrors('craft_id');
    }

    #[Test]
    public function store_creates_one_request_per_craft_for_multi_selection(): void
    {
        $this->actingAsAdmin();
        [$craftA, , $shiftA] = $this->createCraftEventAndShift();

        $craftB = Craft::factory()->create();
        $shiftB = Shift::factory()->create([
            'event_id' => $shiftA->event_id,
            'craft_id' => $craftB->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);

        $response = $this->post(route('commit-shift-workflow-request.store'), [
            'craft_ids' => [$craftA->id, $craftB->id],
            'week_number' => 19,
            'year' => 2026,
        ]);

        $response->assertRedirect();

        // Pro Gewerk EINE eigene pending-Anfrage mit der jeweiligen Schicht
        foreach ([[$craftA, $shiftA], [$craftB, $shiftB]] as [$craft, $shift]) {
            $request = ShiftPlanRequest::query()
                ->where('craft_id', $craft->id)
                ->where('week_number', 19)
                ->where('year', 2026)
                ->first();

            $this->assertNotNull($request);
            $this->assertSame('pending', $request->status);
            $this->assertSame($request->id, $shift->fresh()->current_request_id);
        }

        $this->assertSame(2, ShiftPlanRequest::query()->count());
    }

    #[Test]
    public function store_accepts_empty_craft_ids_only_with_craft_id(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('commit-shift-workflow-request.store'), [
            'craft_ids' => [],
            'week_number' => 19,
            'year' => 2026,
        ]);

        $response->assertSessionHasErrors(['craft_id', 'craft_ids']);
    }

    #[Test]
    public function approving_request_commits_attached_shifts(): void
    {
        $this->actingAsAdmin();
        [$craft, , $shift] = $this->createCraftEventAndShift();

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $request = ShiftPlanRequest::query()->where('craft_id', $craft->id)->firstOrFail();

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        // If the shift was attached during store(), it should be committed now.
        // We don't require the shift to be linked (depends on week-range overlap
        // logic), but we DO require: no shifts in this request remain uncommitted.
        $stillUncommitted = Shift::query()
            ->where('current_request_id', $request->id)
            ->where('is_committed', false)
            ->count();
        $this->assertSame(0, $stillUncommitted);
    }

    #[Test]
    public function approving_request_releases_current_request_id(): void
    {
        $this->actingAsAdmin();
        [$craft, , $shift] = $this->createCraftEventAndShift();

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $request = ShiftPlanRequest::query()->where('craft_id', $craft->id)->firstOrFail();

        // Sanity: shift was attached to the pending request
        $shift->refresh();
        $this->assertSame($request->id, $shift->current_request_id);
        $this->assertTrue((bool) $shift->in_workflow);

        $this->post(route('shift-plan-requests.accept', $request))->assertRedirect();

        // After approval the live pointer must be released so the shift is selectable again,
        // while the shift is committed and no longer "in workflow".
        $shift->refresh();
        $this->assertTrue((bool) $shift->is_committed);
        $this->assertFalse((bool) $shift->in_workflow);
        $this->assertNull($shift->current_request_id);

        // Historie über die Pivot-Tabelle bleibt erhalten
        $this->assertTrue($request->requestedShifts()->where('shift_id', $shift->id)->exists());
    }

    #[Test]
    public function approved_shift_can_be_requested_again_in_a_new_request(): void
    {
        // Regression: previously, accept() left current_request_id pointing at the approved
        // request, so the store() guard permanently excluded the shift from future requests.
        $this->actingAsAdmin();
        [$craft, , $shift] = $this->createCraftEventAndShift();

        // 1. first request + approve
        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();
        $firstRequest = ShiftPlanRequest::query()->where('craft_id', $craft->id)->firstOrFail();
        $this->post(route('shift-plan-requests.accept', $firstRequest))->assertRedirect();

        // 2. request the same craft/week again -> a NEW pending request must pick the shift up again
        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $secondRequest = ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->assertNotSame($firstRequest->id, $secondRequest->id);

        $shift->refresh();
        $this->assertSame($secondRequest->id, $shift->current_request_id);
        $this->assertTrue((bool) $shift->in_workflow);
        $this->assertTrue($secondRequest->requestedShifts()->where('shift_id', $shift->id)->exists());
    }

    #[Test]
    public function deleting_request_releases_its_shifts(): void
    {
        $this->actingAsAdmin();
        [$craft, , $shift] = $this->createCraftEventAndShift();

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $request = ShiftPlanRequest::query()->where('craft_id', $craft->id)->firstOrFail();
        $shift->refresh();
        $this->assertSame($request->id, $shift->current_request_id);

        $this->delete(route('shift-plan-requests.destroy', $request))->assertRedirect();

        $this->assertDatabaseMissing('shift_plan_requests', ['id' => $request->id]);

        // Shift must be fully released, not orphaned with a dangling current_request_id.
        $shift->refresh();
        $this->assertNull($shift->current_request_id);
        $this->assertFalse((bool) $shift->in_workflow);
    }

    #[Test]
    public function shift_with_stale_in_workflow_flag_can_still_be_requested(): void
    {
        // Regression: after a request is deleted, the FK (ON DELETE SET NULL) clears
        // current_request_id but the denormalized in_workflow flag can stay true. The old
        // guard's hard `where('in_workflow', false)` then excluded the shift forever.
        // The new guard relies on request status, not the leaky flag.
        $this->actingAsAdmin();
        [$craft, , $shift] = $this->createCraftEventAndShift();

        $shift->forceFill([
            'in_workflow' => true,
            'current_request_id' => null,
        ])->save();

        $this->post(route('commit-shift-workflow-request.store'), [
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
        ])->assertRedirect();

        $request = ShiftPlanRequest::query()
            ->where('craft_id', $craft->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $shift->refresh();
        $this->assertSame($request->id, $shift->current_request_id);
        $this->assertTrue($request->requestedShifts()->where('shift_id', $shift->id)->exists());
    }
}
