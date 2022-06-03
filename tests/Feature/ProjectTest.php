<?php

use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_user = User::factory()->create();

    $this->department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->sector = Sector::factory()->create();

    $this->genre = Genre::factory()->create();

});

test('aborts invalid requests', function () {

    $this->auth_user->givePermissionTo('create projects');

    $this->actingAs($this->auth_user);

    $this->post('/projects', ['name' => null])->assertInvalid();

});

test('users with the permission can create projects and assign users and departments to it', function () {

    $this->auth_user->givePermissionTo('create projects', 'update users', 'update departments');

    $this->actingAs($this->auth_user);

    $res = $this->post('/projects', [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'sector_id' => $this->sector->id,
        'genre_id' => $this->genre->id,
        'assigned_user_ids' => [$this->assigned_user->id => ['is_admin' => true]],
        'assigned_departments' => [$this->department]
    ]);

    //dd($res);

    $this->assertDatabaseHas('projects', [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'sector_id' => $this->sector->id,
        'genre_id' => $this->genre->id
    ]);

    $project = Project::where('name', 'TestProject')->first();

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $this->assigned_user->id,
        'is_admin' => true,
    ]);

    $this->assertDatabaseHas('department_project', [
        'project_id' => $project->id,
        'department_id' => $this->department->id
    ]);
});

test('users without the permission cant create projects', function () {

    $this->actingAs($this->auth_user);

    $this->post('/projects', [
        'name' => 'TestProject',
        'assigned_users' => [$this->assigned_user],
        'assigned_departments' => [$this->department]
    ])->assertStatus(403);
});

test('users can only view projects they are assigned to', function () {

    $this->department->users()->attach($this->auth_user);
    $this->auth_user->departments()->attach($this->department);

    $this->project->departments()->attach($this->department);
    $this->project->users()->attach($this->auth_user);
    $this->department->projects()->attach($this->project);

    $comment = Comment::factory()->create([
       'project_id' => $this->project->id,
       'user_id' => $this->auth_user
    ]);

    $checklist = Checklist::factory()->create();
    $this->project->checklists()->save($checklist);
    $this->auth_user->private_checklists()->save($checklist);

    $this->actingAs($this->auth_user);

    $response = $this->get("/projects/{$this->project->id}")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Projects/Show')
            ->has('project.comments.0', fn(Assert $page) => $page
                ->hasAll(['id','text', 'created_at', 'user'])
            )
        );

    $response->assertStatus(200);
});

test('users with the permission can update projects and change the role of assigned users', function () {

    $this->auth_user->givePermissionTo('update users','update projects', 'update departments');
    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user, ['is_admin' => true]);

    $this->patch("/projects/{$this->project->id}", [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'assigned_user_ids' => [$this->auth_user->id => ['is_admin' => false]],
        'assigned_departments' => [$this->department]
    ]);

    //dd($res);

    $this->assertDatabaseHas('projects', [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
    ]);

    $project = Project::where('name', 'TestProject')->first();

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $this->auth_user->id,
        'is_admin' => 0
    ]);

    $this->assertDatabaseHas('department_project', [
        'project_id' => $project->id,
        'department_id' => $this->department->id
    ]);


});

test('users with the permission can update projects and delete assigned users', function () {

    $this->auth_user->givePermissionTo('update users','update projects', 'update departments');
    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user, ['is_admin' => true]);

    $this->patch("/projects/{$this->project->id}", [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'assigned_user_ids' => [],
        'assigned_departments' => [$this->department]
    ]);

    $this->assertDatabaseMissing('project_user', [
        'user_id' => $this->auth_user->id
    ]);
});

test('users with the permission can update projects and delete project managers', function () {

    $this->auth_user->givePermissionTo('update users','update projects', 'update departments');
    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user, ['is_manager' => true]);

    $this->patch("/projects/{$this->project->id}", [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'assigned_user_ids' => [],
        'assigned_departments' => [$this->department]
    ]);

    $this->assertDatabaseMissing('project_user', [
        'user_id' => $this->auth_user->id
    ]);
});

test('users with the permission can update projects and make assigned users to admins', function () {

    $this->auth_user->givePermissionTo('update users','update projects', 'update departments');
    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user, ['is_admin' => false]);

    $res = $this->patch("/projects/{$this->project->id}", [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'assigned_user_ids' => [$this->auth_user->id => ['is_admin' => true]],
        'assigned_departments' => [$this->department]
    ]);

    $this->assertDatabaseHas('project_user', [
        'user_id' => $this->auth_user->id,
        'is_admin' => 1
    ]);


});

test('users with the permission can duplicate projects', function() {

    $old_project = Project::factory()->create([
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000-2000',
        'cost_center' => 'DTH CT1',
        'sector_id' => $this->sector->id,
        'genre_id' => $this->genre->id,
    ]);

    $checklist = Checklist::create([
        'project_id' => $old_project->id,
        'name' => 'TestChecklist'
    ]);

    Task::factory()->create([
        'checklist_id' => $checklist->id
    ]);

    $old_project->users()->attach($this->assigned_user);
    $old_project->departments()->attach($this->department);

    $this->auth_user->givePermissionTo('create projects', 'update departments', 'update users');
    $this->actingAs($this->auth_user);

    $res = $this->post("/projects/{$old_project->id}/duplicate");
        //->assertStatus(302);

    //dd($res);

    $this->assertDatabaseHas('projects', [
        'name' => '(Kopie) TestProject'
    ]);

    $new_project = Project::where('name', '(Kopie) TestProject')->first();

    $this->assertDatabaseHas('checklists', [
        'project_id' => $new_project->id,
        'name' => 'TestChecklist',
        'user_id' => null
    ]);

    $this->assertDatabaseHas('project_user', [
        'project_id' => $new_project->id,
        'user_id' => $this->assigned_user->id
    ]);


});

test('users with the permission can delete projects', function () {

    $this->auth_user->givePermissionTo('delete projects');
    $this->project->users()->attach($this->auth_user, ['is_admin' => true]);
    $this->auth_user->projects()->attach($this->project, ['is_admin' => true]);
    $this->actingAs($this->auth_user);

    $this->delete("/projects/{$this->project->id}");

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'deleted_at' => Date::now()
    ]);
});




