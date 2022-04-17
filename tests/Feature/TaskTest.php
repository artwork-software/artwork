<?php

use App\Models\Checklist;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();

    $this->task = Task::factory()->create();

});

test('users who arent assigned to a checklist cant create tasks on it', function () {

    $this->actingAs($this->auth_user);

    $this->post('/tasks', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'deadline' => '2022-12-11',
        'checklist_id' => $this->checklist->id
    ])->assertStatus(403);
});

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




