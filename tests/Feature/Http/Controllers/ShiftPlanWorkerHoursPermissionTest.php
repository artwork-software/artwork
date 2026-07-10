<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

final class ShiftPlanWorkerHoursPermissionTest extends FeatureTestCase
{
    private User $viewer;

    private User $other;

    private Craft $craft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->create([
            'can_work_shifts' => true,
            'work_time_balance' => 120,
        ]);
        $this->other = User::factory()->create([
            'can_work_shifts' => true,
            'work_time_balance' => -60,
        ]);

        $this->craft = Craft::factory()->create();
        $this->viewer->assignedCrafts()->attach($this->craft->id);
        $this->other->assignedCrafts()->attach($this->craft->id);

        $this->givePermission($this->viewer, PermissionEnum::VIEW_SHIFT_PLAN);
    }

    private function givePermission(User $user, PermissionEnum $permission): void
    {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        $user->givePermissionTo($permission->value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchUsersForShifts(): array
    {
        return $this->actingAs($this->viewer)
            ->getJson(route('shifts.workers', [
                'start_date' => now()->startOfWeek()->toDateString(),
                'end_date' => now()->endOfWeek()->toDateString(),
                'craft_ids' => [$this->craft->id],
            ]))
            ->assertOk()
            ->json('usersForShifts');
    }

    /**
     * @param array<int, array<string, mixed>> $usersForShifts
     * @return array<string, mixed>
     */
    private function entryForUser(array $usersForShifts, User $user): array
    {
        foreach ($usersForShifts as $entry) {
            if (($entry['user']['id'] ?? null) === $user->id) {
                return $entry;
            }
        }

        $this->fail(sprintf('User %d fehlt im usersForShifts-Payload.', $user->id));
    }

    #[Test]
    public function without_permission_only_own_hours_are_included(): void
    {
        $usersForShifts = $this->fetchUsersForShifts();

        $ownEntry = $this->entryForUser($usersForShifts, $this->viewer);
        $this->assertSame('2h 0m', $ownEntry['workTimeBalance']);

        $otherEntry = $this->entryForUser($usersForShifts, $this->other);
        $this->assertNull($otherEntry['workTimeBalance']);
        $this->assertSame([], $otherEntry['weeklyWorkingHours']);
    }

    #[Test]
    public function with_permission_foreign_hours_are_included(): void
    {
        $this->givePermission($this->viewer, PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS);

        $usersForShifts = $this->fetchUsersForShifts();

        $otherEntry = $this->entryForUser($usersForShifts, $this->other);
        $this->assertSame('-1h 0m', $otherEntry['workTimeBalance']);
        $this->assertNotSame([], $otherEntry['weeklyWorkingHours']);
    }

    #[Test]
    public function admin_sees_foreign_hours(): void
    {
        Role::query()->firstOrCreate(['name' => RoleEnum::ARTWORK_ADMIN->value, 'guard_name' => 'web']);
        $this->viewer->assignRole(RoleEnum::ARTWORK_ADMIN->value);

        $usersForShifts = $this->fetchUsersForShifts();

        $otherEntry = $this->entryForUser($usersForShifts, $this->other);
        $this->assertSame('-1h 0m', $otherEntry['workTimeBalance']);
    }

    #[Test]
    public function single_worker_reload_hides_foreign_hours_without_permission(): void
    {
        $response = $this->actingAs($this->viewer)
            ->getJson(route('shifts.worker.single', [
                'worker_id' => $this->other->id,
                'worker_type' => 'user',
                'start_date' => now()->startOfWeek()->toDateString(),
                'end_date' => now()->endOfWeek()->toDateString(),
            ]))
            ->assertOk();

        $this->assertNotNull($response->json('worker'));
        $this->assertNull($response->json('worker.workTimeBalance'));
        $this->assertSame([], $response->json('worker.weeklyWorkingHours'));
    }

    #[Test]
    public function single_worker_reload_keeps_own_hours_without_permission(): void
    {
        $response = $this->actingAs($this->viewer)
            ->getJson(route('shifts.worker.single', [
                'worker_id' => $this->viewer->id,
                'worker_type' => 'user',
                'start_date' => now()->startOfWeek()->toDateString(),
                'end_date' => now()->endOfWeek()->toDateString(),
            ]))
            ->assertOk();

        $this->assertSame('2h 0m', $response->json('worker.workTimeBalance'));
    }
}
