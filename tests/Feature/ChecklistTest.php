<?php

use App\Models\ChecklistTemplate;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use Artwork\Modules\Checklist\Models\Checklist;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create([
        'project_id' => $this->project->id
    ]);

    $this->checklist_template = ChecklistTemplate::factory()->create([
        'name' => 'ChecklistTemplateTest'
    ]);

    $this->task = Task::factory()->create([
        'checklist_id' => $this->checklist->id
    ]);

    $this->task_template = TaskTemplate::factory()->create([
        'checklist_template_id' => $this->checklist_template->id,
        'name' => 'TaskTemplateTest'
    ]);

    $this->auth_user->givePermissionTo(\App\Enums\PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value);

    $this->actingAs($this->auth_user);
});

test('users with the permission can create checklists without a template and assign departments to it', function () {

    $name = fake()->company();
    $this->post('/checklists', [
        'name' => $name,
        'project_id' => $this->project->id,
        'user_id' => null,
        'assigned_department_ids' => [$this->assigned_department->id],
        'tasks' => [
            [
                'name' => 'TestTask',
                'description' => 'a description',
                'done' => false,
                'deadline_dt_local' => '2022-4-4',
                'checklist_id' => $this->checklist->id
            ]
        ]
    ]);

    $this->assertDatabaseHas('checklists', [
        'name' => $name,
        'project_id' => $this->project->id
    ]);

    $checklist = Checklist::where('name', $name)->first();

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'checklist_id' => $checklist->id
    ]);

});

test('users with the permission can create checklists with a template and assign departments to it', function () {

    $a = $this->post('/checklists', [
        'name' => null,
        'project_id' => $this->project->id,
        'assigned_department_ids' => [$this->assigned_department->id],
        'template_id' => $this->checklist_template->id,
        'tasks' => null,
        'user_id' => $this->auth_user->id
    ]);

    $this->assertDatabaseHas('checklists', [
        'name' => 'ChecklistTemplateTest',
        'project_id' => $this->project->id,
        'user_id' => $this->auth_user->id
    ]);

    $checklist = Checklist::where('name', 'ChecklistTemplateTest')->first();

    $this->assertDatabaseHas('tasks', [
        'name' => 'TaskTemplateTest',
        'checklist_id' => $checklist->id
    ]);

    $this->assertDatabaseHas('checklist_department', [
        'checklist_id' => $checklist->id,
        'department_id' => $this->assigned_department->id
    ]);

    $this->assertDatabaseHas('department_project', [
        'project_id' => $this->project->id,
        'department_id' => $this->assigned_department->id
    ]);

});

test('users can only view checklists they are assigned to', function () {

    $this->task->checklist()->associate($this->checklist);
    $this->task->save();

    $this->get("/checklists/{$this->checklist->id}")->assertStatus(200);
});

test('users with the permission can delete checklists', function () {

    $this->delete("/checklists/{$this->checklist->id}");

    $this->assertDatabaseMissing('checklists', [
        'id' => $this->checklist->id
    ]);
});




