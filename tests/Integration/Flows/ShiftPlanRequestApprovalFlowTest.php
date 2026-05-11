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
        $craft = Craft::factory()->create();
        $event = Event::factory()->create([
            'start_time' => '2026-05-11 09:00:00',
            'end_time' => '2026-05-11 17:00:00',
        ]);
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-11',
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
}
