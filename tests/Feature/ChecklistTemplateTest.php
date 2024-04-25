<?php

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();
    $this->permissionless_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->checklist_template = ChecklistTemplate::factory()->create();

    $this->checklist = Checklist::factory()->create([
        'name' => 'TestChecklist'
    ]);

    $this->task_template = TaskTemplate::factory()->create([
        'checklist_template_id' => $this->checklist_template->id,
        'name' => 'TaskTemplateTest'
    ]);
    $this->auth_user->givePermissionTo(\Artwork\Modules\Permission\Enums\PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
    $this->actingAs($this->auth_user);
});

test('users with the permission can create template checklists from scratch and without tasks', function () {

    $this->post(route('checklist_templates.store'), [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id,
        'departments' => [$this->assigned_department]
    ]);

    $this->assertDatabaseHas('checklist_templates', [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id
    ]);
});

test('users with the permission can create template checklists from scratch and with tasks', function () {

    $this->post(route('checklist_templates.store'), [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id,
        'departments' => [$this->assigned_department],
        'task_templates' => [
            [
                'name' => 'TestTemplateTask',
                'description' => 'a description',
            ]
        ]
    ]);

    $this->assertDatabaseHas('checklist_templates', [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id
    ]);

    $checklist_template = ChecklistTemplate::where('name', 'TestTemplateChecklist')->first();

    $this->assertDatabaseHas('task_templates', [
        'name' => 'TestTemplateTask',
        'checklist_template_id' => $checklist_template->id
    ]);

});

test('users with the permission can create template checklists from a checklist', function () {

    Task::factory()->create([
        'name' => 'TestTask',
        'checklist_id' => $this->checklist->id
    ]);

    $this->actingAs($this->auth_user);

    $this->post('/checklist_templates', [
        'checklist_id' => $this->checklist->id,
        'user_id' => $this->auth_user->id,
    ]);

    $this->assertDatabaseHas('checklist_templates', [
        'name' => 'TestChecklist',
        'user_id' => $this->auth_user->id
    ]);

    $checklist_template = ChecklistTemplate::where('name', 'TestChecklist')->first();

    $this->assertDatabaseHas('task_templates', [
        'name' => 'TestTask',
        'checklist_template_id' => $checklist_template->id
    ]);
});


test('users without the permission cant create checklist templates', function () {

    $this->actingAs($this->permissionless_user);
    $name = fake()->company();
    $this->post('/checklist_templates', [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id,
        'template_tasks' => [
            [
                'name' => $name,
                'description' => 'a description',
                'checklist_template_id' => $this->checklist_template->id
            ]
        ]
    ])->assertStatus(403);
});

test('users with the permission can see all checklist_templates', function () {

    $this->auth_user->can('view checklist_templates');
    $this->checklist_template->user()->associate($this->auth_user);
    $this->checklist_template->save();
    $this->actingAs($this->auth_user);

    $this->withoutExceptionHandling();
    $response = $this->get('/checklist_templates')
        ->assertInertia(fn(Assert $page) => $page
            ->component('ChecklistTemplates/ChecklistTemplateManagement')
        //->has('checklist_templates.data', 1)
        );

    $response->assertStatus(200);

});

test('users with the permission can update checklist_templates', function () {

    $task_template = TaskTemplate::factory()->create(
        [
            'checklist_template_id' => $this->checklist_template->id
        ]
    );
    $task_template2 = TaskTemplate::factory()->create([
        'checklist_template_id' => $this->checklist_template->id
    ]);

    $name = fake()->company();

    $response = $this->patch("/checklist_templates/{$this->checklist_template->id}", [
        'name' => $name,
        'departments' => [$this->assigned_department],
        'task_templates' => [
            $task_template->toArray(),
            $task_template2->toArray(),
            [
                'name' => 'TestTemplateTask',
                'description' => 'a description',
                'checklist_template_id' => $this->checklist_template->id
            ]
        ]
    ]);

    $this->assertDatabaseHas('checklist_templates', [
        'name' => $name,
    ]);

    $checklist_template = ChecklistTemplate::where('name', $name)->first();

    $this->assertDatabaseHas('task_templates', [
        'name' => $task_template->name,
        'checklist_template_id' => $checklist_template->id
    ]);

    $this->assertDatabaseHas('task_templates', [
        'name' => 'TestTemplateTask',
        'checklist_template_id' => $checklist_template->id
    ]);

});

test('users with the permission can delete checklist_templates', function () {

    $this->delete("/checklist_templates/{$this->checklist_template->id}");

    $this->assertDatabaseMissing('checklist_templates', [
        'id' => $this->checklist_template->id
    ]);
});




