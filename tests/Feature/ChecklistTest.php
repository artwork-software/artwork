<?php

use App\Models\Checklist;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_user = User::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();

    $this->task = Task::factory()->create();

});

test('users with the permission can create checklists and assign users to it', function () {

    $this->auth_user->givePermissionTo('create checklists', 'update users');

    $this->actingAs($this->auth_user);

    $this->post('/checklists', [
        'name' => 'TestChecklist',
        'project_id' => $this->project->id,
        'assigned_user_ids' => [$this->assigned_user->id],
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

    $this->assertDatabaseHas('checklist_user', [
        'checklist_id' => $checklist->id,
        'user_id' => $this->assigned_user->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'checklist_id' => $checklist->id
    ]);

});

test('users without the permission cant create checklists', function () {

    $this->actingAs($this->auth_user);

    $this->post('/checklists', [
        'name' => 'TestChecklist',
        'project_id' => $this->project->id,
        'assigned_users' => [$this->assigned_user],
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

    $this->checklist->users()->attach($this->auth_user);

    $this->task->checklist()->associate($this->checklist);
    $this->task->save();

    $this->auth_user->givePermissionTo('view checklists');
    $this->actingAs($this->auth_user);

    $response = $this->get("/checklists/{$this->checklist->id}")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Checklists/Show')
            ->has('checklist', fn(Assert $page) => $page
                ->hasAll(['id', 'name', 'users', 'tasks'])
            )
        );

    $response->assertStatus(200);
});

test('users with the permission can update checklists', function () {

    $this->auth_user->givePermissionTo('update users', 'update checklists');
    $this->actingAs($this->auth_user);

    $this->checklist->users()->attach($this->auth_user);

    $this->patch("/checklists/{$this->checklist->id}", [
        'name' => 'TestChecklist',
        'assigned_user_ids' => [$this->assigned_user->id],
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

    $this->assertDatabaseHas('checklist_user', [
        'checklist_id' => $checklist->id,
        'user_id' => $this->assigned_user->id,
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'checklist_id' => $checklist->id
    ]);


});

test('users with the permission can delete checklists', function () {

    $this->auth_user->givePermissionTo('delete checklists');
    $this->checklist->users()->attach($this->auth_user);
    $this->actingAs($this->auth_user);

    $this->delete("/checklists/{$this->checklist->id}");

    $this->assertDatabaseMissing('checklists', [
        'id' => $this->checklist->id
    ]);
});




