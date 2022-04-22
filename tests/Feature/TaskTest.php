<?php

use App\Models\Checklist;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();

    $this->task = Task::factory()->create();

    $this->sector = Sector::factory()->create();

    $this->genre = Genre::factory()->create();

    $this->department = Department::factory()->create();

});

/*
test('users who arent assigned to a checklist cant create tasks on it', function () {

    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ])->assertStatus(403);
});
*/

/*
test('users that are assigned to a checklist can create tasks for it', function () {

    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->departments()->attach($this->assigned_department);
    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ]);

});
*/

test('users that are admins can create tasks for any checklist in any project', function () {

    $this->auth_user->assignRole('admin');
    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ]);

});

test('users that are project admins can create tasks for any checklist in their project', function () {

    $project_admin = User::factory()->create();
    $project_admin->givePermissionTo('create projects', 'update users', 'update departments');
    $this->actingAs($project_admin);

    $this->post('/projects', [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'sector_id' => $this->sector->id,
        'genre_id' => $this->genre->id,
        'assigned_user_ids' => [$project_admin->id => ['is_admin' => true]],
        'assigned_departments' => [$this->department]
    ]);

    $project = Project::where('name', 'TestProject')->first();

    $checklist = Checklist::factory()->create([
        'project_id' => $project->id
    ]);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $checklist->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $checklist->id
    ]);
});

test('users that are project managers can create tasks for any checklist in their project', function() {

    $project_manager = User::factory()->create();
    $project_manager->givePermissionTo('create projects', 'update users', 'update departments');
    $this->actingAs($project_manager);

    $this->post('/projects', [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'sector_id' => $this->sector->id,
        'genre_id' => $this->genre->id,
        'assigned_user_ids' => [$project_manager->id => ['is_manager' => true]],
        'assigned_departments' => [$this->department]
    ]);

    $project = Project::where('name', 'TestProject')->first();

    $checklist = Checklist::factory()->create([
        'project_id' => $project->id
    ]);

    $project->checklists()->save($checklist);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $checklist->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $checklist->id
    ]);
});

test('users who are assigned to a checklist can update its tasks', function () {

    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->departments()->attach($this->assigned_department);
    $this->actingAs($this->auth_user);

    $this->patch("/tasks/{$this->task->id}", [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ]);


});

test('users who are assigned to a checklist can delete its tasks', function () {

    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->departments()->attach($this->assigned_department);

    $this->actingAs($this->auth_user);

    $this->patch("/tasks/{$this->task->id}", [
        'checklist_id' => $this->checklist->id
    ]);

    $this->delete("/tasks/{$this->task->id}");

    $this->assertDatabaseMissing('tasks', [
        'id' => $this->task->id
    ]);
});




