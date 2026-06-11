<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Welle-1-Regression: Schicht-Mutationsrouten sind nur noch mit den Permissions
 * erreichbar, die das Frontend bereits voraussetzt (B2), das tote
 * ShiftCommitWorkflowRequests-Altsystem ist entfernt (B1), die event-gebundenen
 * Erstellungspfade sind entfernt (B4/B6) und check-collisions verlangt Auth (B3).
 */
final class ShiftRouteAuthorizationTest extends FeatureTestCase
{
    #[Test]
    public function user_without_planning_permission_cannot_mutate_shifts(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $shift = Shift::factory()->create();

        $this->deleteJson(route('shifts.destroy', $shift))->assertForbidden();
        $this->patchJson(route('event.shift.update', $shift), [])->assertForbidden();
        $this->patchJson(route('event.shift.update.updateTime', $shift), [])->assertForbidden();
        $this->patchJson(route('event.shift.update.updateDescription', $shift), [])->assertForbidden();
        $this->postJson(route('shifts.multi.delete'), [])->assertForbidden();
        $this->postJson(route('shifts.multi.duplicate'), [])->assertForbidden();
        $this->postJson(route('shift.multi.edit.save'), [])->assertForbidden();
        $this->deleteJson(route('shift.removeUserByType', ['usersPivotId' => 1, 'userType' => 0]))
            ->assertForbidden();
        $this->deleteJson(route('shift.removeAllUsers', $shift))->assertForbidden();
        $this->patchJson(route('shifts.qualifications.add', $shift), [])->assertForbidden();
        $this->patchJson(route('shifts.qualifications.overbook.increase', $shift), [])->assertForbidden();
        $this->patchJson(route('shifts.qualifications.overbook.decrease', $shift), [])->assertForbidden();
        $this->postJson(route('event.shift.store.without.event'), [])->assertForbidden();
        $this->postJson(route('event.shift.store.multi.add'), [])->assertForbidden();
        $this->postJson(route('shift.plan.user.cell.update'), [])->assertForbidden();
        $this->postJson(route('multi-edit.cell.delete'), [])->assertForbidden();
        $this->postJson(route('multi-edit.calendar.cell.delete'), [])->assertForbidden();
        $this->postJson(route('shifts.createFromPresets'), [])->assertForbidden();
        $this->postJson(route('shifts.updateIndividualShiftTime'), [])->assertForbidden();
        $this->postJson(route('shifts.updateShortDescription'), [])->assertForbidden();
    }

    #[Test]
    public function user_without_commit_permission_cannot_commit_shifts(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $shift = Shift::factory()->create();

        $this->postJson(route('shifts.commit'), [])->assertForbidden();
        $this->postJson(route('shift.change.commit.status', $shift), [])->assertForbidden();
        $this->patchJson(route('update.shift.commitment'), [])->assertForbidden();
        $this->postJson(route('commit-shift-workflow-request.store'), [])->assertForbidden();
    }

    #[Test]
    public function user_without_approver_entry_cannot_decide_shift_plan_requests(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::CAN_COMMIT_SHIFTS->value,
        ]);
        $request = $this->createPendingRequest();

        $this->postJson(route('shift-plan-requests.accept', $request))->assertForbidden();
        $this->postJson(route('shift-plan-requests.reject', $request))->assertForbidden();
        $this->getJson(route('shift-plan-requests.show', $request))->assertForbidden();
        $this->deleteJson(route('shift-plan-requests.destroy', $request))->assertForbidden();
        $this->get(route('shifts.approvals.review'))->assertForbidden();
        $this->get(route('shifts.approvals.changes'))->assertForbidden();
    }

    #[Test]
    public function planner_passes_authorization_on_shift_destroy(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $shift = Shift::factory()->create();

        $response = $this->delete(route('shifts.destroy', $shift));

        $this->assertNotSame(403, $response->getStatusCode());
        // destroy() force-deleted die Schicht (kein Soft-Delete auf diesem Pfad)
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function workflow_user_can_accept_shift_plan_request(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        ShiftCommitWorkflowUser::create(['user_id' => $user->id]);
        $request = $this->createPendingRequest();

        $response = $this->post(route('shift-plan-requests.accept', $request));

        $this->assertNotSame(403, $response->getStatusCode());
        $this->assertSame('approved', $request->fresh()->status);
    }

    #[Test]
    public function guest_cannot_check_collisions(): void
    {
        $this->postJson(route('shift.check-collisions'), [])->assertUnauthorized();
    }

    #[Test]
    public function legacy_commit_workflow_request_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('shifts.requestCommit'));
        $this->assertFalse(Route::has('shifts.commit-requests.index'));
        $this->assertFalse(Route::has('shifts.commit-requests.approve'));
        $this->assertFalse(Route::has('shifts.commit-requests.decline'));
    }

    #[Test]
    public function event_bound_shift_creation_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('event.shift.store'));
        $this->assertFalse(Route::has('shift.preset.import'));
        $this->assertFalse(Route::has('shift-presets.store'));
    }

    #[Test]
    public function my_requests_index_route_is_reachable(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shift-plan-requests.my.index'))->assertSuccessful();
    }

    private function createPendingRequest(): ShiftPlanRequest
    {
        $craft = Craft::factory()->create();

        return ShiftPlanRequest::create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $this->adminUser()->id,
            'requested_at' => now(),
        ]);
    }
}
