<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Feature\FeatureTestCase;

final class WorkTimeChangeRequestDecisionTest extends FeatureTestCase
{
    private function givePermission(User $user, PermissionEnum $permission): void
    {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        $user->givePermissionTo($permission->value);
    }

    private function createRequest(Craft $craft, string $status = 'pending'): WorkTimeChangeRequest
    {
        $worker = User::factory()->create();

        return WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'request_start_time' => '08:00',
            'request_end_time' => '16:00',
            'craft_id' => $craft->id,
            'status' => $status,
            'requested_by' => $worker->id,
        ]);
    }

    #[Test]
    public function craft_shift_planner_can_decline_a_pending_request(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);
        $craft->craftShiftPlaner()->attach($planner->id);

        $request = $this->createRequest($craft);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $request), [
                'decline_message' => 'Passt leider nicht.',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame($planner->id, $request->declined_by);
        $this->assertSame('Passt leider nicht.', $request->decline_comment);
    }

    #[Test]
    public function planner_may_decide_for_crafts_assignable_by_all(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        $request = $this->createRequest($craft);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $request), [
                'decline_message' => 'Nein.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
    }

    #[Test]
    public function user_without_shift_planner_permission_gets_403(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $user = User::factory()->create();

        $request = $this->createRequest($craft);

        $this->actingAs($user)
            ->post(route('worktime.change-request.decline', $request))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('worktime.change-request.approve', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    #[Test]
    public function planner_of_another_craft_gets_403(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        $request = $this->createRequest($craft);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $request))
            ->assertForbidden();

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    #[Test]
    public function already_decided_requests_cannot_be_decided_again(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);

        $approved = $this->createRequest($craft, 'approved');
        $rejected = $this->createRequest($craft, 'rejected');

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $approved))
            ->assertForbidden();

        $this->actingAs($planner)
            ->post(route('worktime.change-request.decline', $rejected))
            ->assertForbidden();
    }
}
