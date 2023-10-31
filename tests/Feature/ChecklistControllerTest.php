<?php

namespace Tests\Feature;

use App\Enums\PermissionNameEnum;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChecklistControllerTest extends TestCase
{
    public function testChecklistUpdateAuthorization(): void
    {
        $checklist = Checklist::factory()->create();
        /** @var Department $department */
        $department = Department::factory()->create();
        Permission::firstOrCreate(['name' => PermissionNameEnum::DEPARTMENT_UPDATE->value]);
        Permission::firstOrCreate(['name' => PermissionNameEnum::CHECKLIST_UPDATE->value]);

        // assert unauthenticated
        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]))->assertUnauthorized();

        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // user not authorized
        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [])
            ->assertForbidden();

        //@todo bogus permission, never checked
       // $user->givePermissionTo(PermissionNameEnum::CHECKLIST_UPDATE->value);
        $user->departments()->sync([$department->id]);

        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
            'assigned_department_ids' => [$department->id]
        ])->assertForbidden();

        $user->givePermissionTo(PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $this->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [])
            ->assertFound();
    }

    public function testChecklistUpdateChecklist()
    {
        $checklist = Checklist::factory()->create();

        $this->actingAs($this->adminUser())
            ->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
                'name' => 'New Name',
            ])->assertSuccessful();

        $this->assertDatabaseHas('checklists', [
            'name' => 'New Name',
        ]);
    }

    public function testChecklistUpdateTasks()
    {
        $checklist = Checklist::factory()->create();

        $this->actingAs($this->adminUser())
            ->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
                'tasks' => [
                    [
                        'name' => 'Some Name',
                        'done' => false,
                        'order' => 2,
                    ]
                ],
            ])->assertSuccessful();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Some Name',
            'checklist_id' => $checklist->id,
        ]);
    }

    public function testChecklistUpdateDepartments()
    {
        $checklist = Checklist::factory()->create();
        $department = Department::factory()->create();

        $this->actingAs($this->adminUser())
            ->patchJson(route('checklists.update', ['checklist' => $checklist->id]), [
                'assigned_department_ids' => [$department->id],
            ])->assertSuccessful();

        $this->assertDatabaseHas('checklist_department', [
            'department_id' => $department->id,
            'checklist_id' => $checklist->id,
        ]);
    }
}
