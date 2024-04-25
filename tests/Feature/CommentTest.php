<?php

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;

beforeEach(function (): void {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();

    $this->task = Task::factory()->create();

    $this->comment = Comment::factory()->create();
});

test('users that are assigned to a project can create comments on it', function (): void {

    $this->project->users()->attach($this->auth_user);
    $this->project->comments()->save($this->comment);

    $this->actingAs($this->auth_user);

    $this->post('/comments', [
        'text' => 'TestComment',
        'project_id' => $this->project->id,
        'user_id' => $this->auth_user->id
    ]);

    $this->assertDatabaseHas('comments', [
        'text' => 'TestComment',
        'project_id' => $this->project->id
    ]);
});

test('users cant create comments on projects they arent assigned to', function (): void {

    $this->actingAs($this->auth_user);

    $this->post('/comments', [
        'text' => 'TestComment',
        'project_id' => $this->project->id,
        'user_id' => $this->auth_user->id
    ])->assertStatus(403);
});

/**
 * @todo the setup process is currently broken
 */
//test('users can only view comments from projects they are assigned to', function () {



//    $this->project->users()->attach($this->auth_user);
//    $this->project->comments()->save($this->comment);
//
//    $this->actingAs($this->auth_user);
//
//    $response = $this->get("/projects/{$this->project->id}")
//        ->assertInertia(fn(Assert $page) => $page
//            ->component('Projects/Show')
//            ->has('project.comments.0', fn(Assert $page) => $page
//                ->hasAll(['id', 'text', 'user_id', 'created'])
//            )
//        );
//
//    $response->assertStatus(200);
//});

test('users can update their own comments', function (): void {

    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user);
    $this->project->comments()->save($this->comment);
    $this->auth_user->comments()->save($this->comment);


    $this->patch("/comments/{$this->comment->id}", [
        'text' => 'TestComment'
    ]);

    $this->assertDatabaseHas('comments', [
        'text' => 'TestComment'
    ]);
});

test('users can delete their own comments', function (): void {

    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user);
    $this->project->comments()->save($this->comment);
    $this->auth_user->comments()->save($this->comment);

    $this->delete("/comments/{$this->comment->id}");

    $this->assertDatabaseMissing('comments', [
        'id' => $this->comment->id
    ]);
});
