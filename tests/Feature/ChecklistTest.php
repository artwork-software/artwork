<?php

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\User;

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

    $this->auth_user->givePermissionTo(\Artwork\Modules\Permission\Enums\PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);

    $this->actingAs($this->auth_user);
});

test('users with the permission can create checklists without a template and assign departments to it', function () {

    $name = fake()->company();
    $this->project->users()->attach($this->auth_user);
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

    $this->project->users()->attach($this->auth_user->id);
    $this->post('/checklists', [
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




