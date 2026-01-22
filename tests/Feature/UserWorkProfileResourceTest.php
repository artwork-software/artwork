<?php

namespace Tests\Feature;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Http\Resources\UserWorkProfileResource;
use Artwork\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

class UserWorkProfileResourceTest extends TestCase
{
    public function test_accessible_crafts_includes_explicit_planners_and_managers(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::SHIFT_PLANNER->value);

        // 1. Craft: assignable_by_all = true (should be visible)
        $publicCraft = Craft::factory()->create(['assignable_by_all' => true, 'name' => 'Public Craft']);

        // 2. Craft: assignable_by_all = false, user is explicit planner
        $plannerCraft = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Planner Craft']);
        $plannerCraft->craftShiftPlaner()->attach($user->id);

        // 3. Craft: assignable_by_all = false, user is manager
        $managerCraft = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Manager Craft']);
        $managerCraft->managingUsers()->attach($user->id);

        // 4. Craft: assignable_by_all = false, user has NO connection (should not be visible)
        $privateCraft = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Private Craft']);

        // 5. Craft: assignable_by_all = false, user is assigned to work in it (should be visible via assignedCrafts)
        $workCraft = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Work Craft']);
        $user->assignedCrafts()->attach($workCraft->id);

        $allCrafts = Craft::all();
        $resource = new UserWorkProfileResource($user, $allCrafts);
        $data = $resource->toArray(request());

        $accessibleCraftIds = collect($data['accessibleCrafts'])->pluck('id')->toArray();

        $this->assertContains($publicCraft->id, $accessibleCraftIds, 'Public craft should be accessible');
        $this->assertContains($workCraft->id, $accessibleCraftIds, 'Work craft should be accessible via assignedCrafts');

        // These are expected to FAIL before the fix
        $this->assertContains($plannerCraft->id, $accessibleCraftIds, 'Planner craft should be accessible');
        $this->assertContains($managerCraft->id, $accessibleCraftIds, 'Manager craft should be accessible');

        $this->assertNotContains($privateCraft->id, $accessibleCraftIds, 'Private craft should NOT be accessible');
    }
}
