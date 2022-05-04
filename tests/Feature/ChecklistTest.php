<?php

use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();

    $this->task = Task::factory()->create();

    $this->checklist_template = ChecklistTemplate::factory()->create([
        'name' => 'ChecklistTemplateTest'
    ]);

    $this->task_template = TaskTemplate::factory()->create([
        'checklist_template_id' => $this->checklist_template->id,
        'name' => 'TaskTemplateTest'
    ]);

});

test('users with the permission can create checklists without a template and assign departments to it', function () {

    $this->auth_user->givePermissionTo('create checklists', 'update departments');

    $this->actingAs($this->auth_user);

    $this->post('/checklists', [
        'name' => 'TestChecklist',
        'project_id' => $this->project->id,
        'user_id' => null,
        'assigned_department_ids' => [$this->assigned_department->id],
        'tasks' => [
            [
                'name' => 'TestTask',
                'description' => 'a description',
                'done' => false,
                'deadline' => '2022-4-4',
                'checklist_id' => $this->checklist->id
            ]
        ]
    ]);

    $this->assertDatabaseHas('checklists', [
        'name' => 'TestChecklist',
        'project_id' => $this->project->id
    ]);

    $checklist = Checklist::where('name', 'TestChecklist')->first();

    $this->assertDatabaseHas('checklist_department', [
        'checklist_id' => $checklist->id,
        'department_id' => $this->assigned_department->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'checklist_id' => $checklist->id
    ]);

});

test('users with the permission can create checklists with a template and assign departments to it', function () {

    $this->auth_user->givePermissionTo('create checklists', 'update departments');

    $this->checklist_template->departments()->attach($this->assigned_department);

    $this->actingAs($this->auth_user);

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

    $this->assertDatabaseHas('checklist_department', [
        'checklist_id' => $checklist->id,
        'department_id' => $this->assigned_department->id
    ]);

    $this->assertDatabaseHas('department_project', [
        'project_id' => $this->project->id,
        'department_id' => $this->assigned_department->id
    ]);

});

test('users without the permission cant create checklists', function () {

    $this->actingAs($this->auth_user);

    $this->post('/checklists', [
        'name' => 'TestChecklist',
        'project_id' => $this->project->id,
        'assigned_department_ids' => [$this->assigned_department->id],
        'template_id' => null,
        'tasks' => [
            [
                'name' => 'TestTask',
                'description' => 'a description',
                'done' => false,
                'deadline' => '2022-4-4',
                'checklist_id' => $this->checklist->id
            ]
        ]
    ])->assertStatus(403);
});

test('users can only view checklists they are assigned to', function () {

    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->departments()->attach($this->assigned_department);

    $this->task->checklist()->associate($this->checklist);
    $this->task->save();

    $this->auth_user->givePermissionTo('view checklists');
    $this->actingAs($this->auth_user);

    $response = $this->get("/checklists/{$this->checklist->id}")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Checklists/Show')
            ->has('checklist', fn(Assert $page) => $page
                ->hasAll(['id', 'name', 'departments', 'tasks'])
            )
        );

    $response->assertStatus(200);
});

test('users with the permission can update checklists', function () {

    $this->auth_user->givePermissionTo('update departments', 'update checklists');
    $this->actingAs($this->auth_user);

    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->departments()->attach($this->assigned_department);

    $res = $this->patch("/checklists/{$this->checklist->id}", [
        'name' => 'TestChecklist',
        'assigned_department_ids' => [$this->assigned_department->id],
        'tasks' => [
            [
                'name' => 'TestTask',
                'description' => 'a description',
                'done' => false,
                'deadline' => '2022-4-4',
                'checklist_id' => $this->checklist->id
            ]
        ]
    ]);

    $this->assertDatabaseHas('checklists', [
        'name' => 'TestChecklist'
    ]);

    $checklist = Checklist::where('name', 'TestChecklist')->first();

    $this->assertDatabaseHas('checklist_department', [
        'checklist_id' => $checklist->id,
        'department_id' => $this->assigned_department->id,
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'checklist_id' => $checklist->id
    ]);


});

test('users with the permission can delete checklists', function () {

    $this->auth_user->givePermissionTo('delete checklists');
    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->departments()->attach($this->assigned_department);

    $this->actingAs($this->auth_user);

    $this->delete("/checklists/{$this->checklist->id}");

    $this->assertDatabaseMissing('checklists', [
        'id' => $this->checklist->id
    ]);
});




