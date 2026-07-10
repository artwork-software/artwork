<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftPlanRequestControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_review(): void
    {
        $this->get(route('shifts.approvals.review'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_review_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.review'))->assertOk();
    }

    #[Test]
    public function admin_can_view_my_requests_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.requests'))->assertOk();
    }

    #[Test]
    public function admin_can_view_changes(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.changes'))->assertOk();
    }

    #[Test]
    public function admin_can_view_past_requests(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $response = $this->get(route('shifts.approvals.past-requests', [
            'craft' => $craft->id,
            'status' => 'approved',
            'offset' => 0,
        ]));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_create_shift_plan_request(): void
    {
        $this->post(route('commit-shift-workflow-request.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_show_shift_plan_request(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
        ]);

        $response = $this->get(route('shift-plan-requests.show', $request));

        $response->assertOk();
    }

    #[Test]
    public function show_contains_navigation_between_requests_of_same_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw28 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 28, 'year' => 2026]);
        $kw29 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 29, 'year' => 2026]);
        $kw30 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 30, 'year' => 2026]);
        // Anfrage eines anderen Gewerks darf in der Navigation nicht auftauchen
        ShiftPlanRequest::factory()->create(['week_number' => 29, 'year' => 2026]);

        $this->get(route('shift-plan-requests.show', $kw29))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftPlanRequests/Show')
                ->where('navigation.previous.id', $kw28->id)
                ->where('navigation.next.id', $kw30->id));
    }

    #[Test]
    public function navigation_is_empty_at_the_edges(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw28 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 28, 'year' => 2026]);
        $kw29 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 29, 'year' => 2026]);

        $this->get(route('shift-plan-requests.show', $kw28))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.previous', null)
                ->where('navigation.next.id', $kw29->id));

        $this->get(route('shift-plan-requests.show', $kw29))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.previous.id', $kw28->id)
                ->where('navigation.next', null));
    }

    #[Test]
    public function show_shifts_include_room_name(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $room = Room::factory()->create(['name' => 'Halle K1']);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 28,
            'year' => 2026,
        ]);
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'start_date' => '2026-07-07',
            'end_date' => '2026-07-07',
        ]);
        $request->requestedShifts()->attach($shift->id, ['snapshot' => json_encode([])]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('shifts.0.room_name', 'Halle K1'));
    }

    #[Test]
    public function navigation_orders_across_year_boundary(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw52 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 52, 'year' => 2026]);
        $kw1 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 1, 'year' => 2027]);

        $this->get(route('shift-plan-requests.show', $kw52))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.next.id', $kw1->id));
    }
}
