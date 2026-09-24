<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Illuminate\Support\Facades\Event;
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

    #[Test]
    public function approving_a_request_sets_the_individual_time_like_the_shift_plan(): void
    {
        // Regression: Die Genehmigung schrieb nur die Uhrzeiten — ein altes +1-Tag-end_date (frühere
        // Über-Mitternacht-Zeit) blieb stehen (28h-Zuweisung), Stunden-Cache und Ansichten blieben alt.
        Event::fake([UpdateShiftInShiftPlan::class]);
        $craft = Craft::factory()->create(['assignable_by_all' => true]);
        $planner = User::factory()->create();
        $this->givePermission($planner, PermissionEnum::SHIFT_PLANNER);
        $worker = User::factory()->create();
        $shiftDate = now()->addDays(3)->toDateString();
        $nextDay = now()->addDays(4)->toDateString();

        $shift = Shift::factory()->create([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => $shiftDate,
            'end_date' => $shiftDate,
            'start' => '10:00:00',
            'end' => '18:00:00',
        ]);
        $qualification = ShiftQualification::factory()->create();
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => $qualification->id,
            'craft_abbreviation' => 'X',
            'start_date' => $shiftDate,
            'end_date' => $nextDay,
            'start_time' => '22:00',
            'end_time' => '02:00',
        ]);

        $request = WorkTimeChangeRequest::create([
            'user_id' => $worker->id,
            'shift_id' => $shift->id,
            'request_start_time' => '08:00',
            'request_end_time' => '16:00',
            'craft_id' => $craft->id,
            'status' => 'pending',
            'requested_by' => $worker->id,
        ]);

        $this->actingAs($planner)
            ->post(route('worktime.change-request.approve', $request))
            ->assertRedirect();

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $worker->id,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'start_date' => $shiftDate,
            'end_date' => $shiftDate,
        ]);
        $this->assertSame('approved', $request->fresh()->status);
        Event::assertDispatched(UpdateShiftInShiftPlan::class);
    }
}
