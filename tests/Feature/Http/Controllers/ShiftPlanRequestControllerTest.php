<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
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
}
