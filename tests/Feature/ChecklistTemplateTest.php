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

    $this->checklist_template = ChecklistTemplate::factory()->create();

    $this->task_template = TaskTemplate::factory()->create([
        'checklist_template_id' => $this->checklist_template->id,
        'name' => 'TaskTemplateTest'
    ]);

});

test('users with the permission can create template checklists', function () {

    $this->auth_user->givePermissionTo('create checklist_templates');

    $this->actingAs($this->auth_user);

    $this->post('/checklist_templates', [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id,
        'task_templates' => [
            [
                'name' => 'TestTemplateTask',
                'description' => 'a description',
                'checklist_template_id' => $this->checklist_template->id
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


test('users without the permission cant create checklist templates', function () {

    $this->actingAs($this->auth_user);

    $this->post('/checklist_templates', [
        'name' => 'TestTemplateChecklist',
        'user_id' => $this->auth_user->id,
        'template_tasks' => [
            [
                'name' => 'TestTemplateTask',
                'description' => 'a description',
                'checklist_template_id' => $this->checklist_template->id
            ]
        ]
    ])->assertStatus(403);
});

test('users with the permission can see all checklist_templates', function() {

    $this->auth_user->can('view checklist_templates');
    $this->checklist_template->user()->associate($this->auth_user);
    $this->checklist_template->save();
    $this->checklist_template->departments()->attach($this->assigned_department);
    $this->actingAs($this->auth_user);

    $response = $this->get('/checklist_templates')
        ->assertInertia(fn(Assert $page) => $page
            ->component('ChecklistTemplates/ChecklistTemplateManagement')
            //->has('checklist_templates.data', 1)
        );

    $response->assertStatus(200);

});

test('users with the permission can update checklist_templates', function () {

    $this->auth_user->givePermissionTo('update checklist_templates');
    $this->actingAs($this->auth_user);

    $this->patch("/checklist_templates/{$this->checklist_template->id}", [
        'name' => 'TestChecklistNew',
    ]);

    $this->assertDatabaseHas('checklist_templates', [
        'name' => 'TestChecklistNew'
    ]);

});

test('users with the permission can delete checklist_templates', function () {

    $this->auth_user->givePermissionTo('delete checklist_templates');
    $this->actingAs($this->auth_user);

    $this->delete("/checklist_templates/{$this->checklist_template->id}");

    $this->assertDatabaseMissing('checklist_templates', [
        'id' => $this->checklist_template->id
    ]);
});




