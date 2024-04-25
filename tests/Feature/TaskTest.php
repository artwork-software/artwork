<?php

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();

    $this->task = Task::factory()->create([
        'checklist_id' => $this->checklist->id
    ]);

    $this->sector = Sector::factory()->create();

    $this->genre = Genre::factory()->create();

    $this->department = Department::factory()->create();

    $this->actingAs($this->auth_user);
});


test('users can view a list of all their tasks, eg private or from checklists they are assigned to', function (): void {

    $this->auth_user->assignRole(RoleEnum::ARTWORK_ADMIN->value);

    $this->assigned_department->users()->attach($this->auth_user);

    $response = $this->get("/tasks/own")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Tasks/OwnTasksManagement'));
        //->has('tasks.data', 10)


    $response->assertStatus(200);
});

/** @todo route does not exist anymore? */

//test('users can view the page to edit a task if they are assigned to the checklist', function () {
//
//    $this->auth_user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
//
//    $this->assigned_department->users()->attach($this->auth_user);
//    $this->checklist->users()->attach($this->auth_user);
//    $this->patch("/tasks/{$this->task->id}/", [
//        'name' => 'TestTask',
//        'description' => "This is a description",
//        'done' => true
//    ]);
//
//    $response = $this->get("/tasks/{$this->task->id}");
//
//    $response->assertOk();
//});


test('users who arent assigned to a checklist cant create tasks on it', function (): void {

    $this->project->users()->attach($this->auth_user);

    $checklist = Checklist::factory()->create([
        'project_id' => $this->project->id
    ]);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $checklist->id
    ])->assertStatus(403);
});


test('users that are assigned to a checklist can create tasks without a deadline for it', function (): void {

    $this->project->users()->attach($this->auth_user);
    $this->assigned_department->users()->attach($this->auth_user);

    $checklist = Checklist::factory()->create([
        'project_id' => $this->project->id
    ]);

    $this->auth_user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $checklist->id,
        'deadline' => null
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $checklist->id,
        'deadline' => null
    ]);
});

test('users that are assigned to a checklist can create tasks with a deadline for it', function (): void {

    $this->project->users()->attach($this->auth_user);

    $checklist = Checklist::factory()->create([
        'project_id' => $this->project->id
    ]);
    $checklist->users()->attach($this->auth_user);
    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $checklist->id,
        'deadline' => '2022-05-28T17:48',
        'is_Dirty' => true
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $checklist->id,
        'deadline' => '2022-05-28T17:48'
    ]);
});


test('users that are admins can create tasks for any checklist in any project', function (): void {

    $this->auth_user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $this->checklist->id
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $this->checklist->id
    ]);
});

test('users who are assigned to a checklist can update its tasks', function (): void {

    $this->assigned_department->users()->attach($this->auth_user);
    $this->checklist->project()->associate($this->project);
    $this->checklist->save();
    $this->checklist->users()->attach($this->auth_user);
    $this->actingAs($this->auth_user);

    $res = $this->patch("/tasks/{$this->task->id}", [
        'name' => 'TestTask',
        'description' => "This is a description",
        'done' => true
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $this->checklist->id,
        'done_at' => Date::now(),
        'user_id' => $this->auth_user->id
    ]);

    $res = $this->patch("/tasks/{$this->task->id}", [
        'name' => 'TestTask',
        'description' => "This is a description",
        'done' => false
    ]);

    $this->assertDatabaseHas('tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $this->checklist->id,
        'done_at' => null,
        'user_id' => null
    ]);
});

test('users who are assigned to a checklist can delete its tasks', function (): void {

    $this->assigned_department->users()->attach($this->auth_user);

    $this->actingAs($this->auth_user);

    $this->patch("/tasks/{$this->task->id}", [
        'checklist_id' => $this->checklist->id
    ]);

    $this->delete("/tasks/{$this->task->id}");

    $this->assertDatabaseMissing('tasks', [
        'id' => $this->task->id
    ]);
});
