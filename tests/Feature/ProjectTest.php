<?php

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_user = User::factory()->create();

    $this->department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->sector = Sector::factory()->create();

    $this->genre = Genre::factory()->create();

});

test('aborts invalid requests', function () {

    $this->actingAs($this->auth_user);

    $this->post('/projects', ['name' => null])->assertInvalid();

});

//Tests can currently not work

//test('users with the permission can create projects and assign users and departments to it', function () {
//
//    $this->auth_user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
//
//    $this->actingAs($this->auth_user);
//
//    $res = $this->post('/projects', [
//        'name' => 'TestProject',
//        'description' => 'a description',
//        'number_of_participants' => '1000',
//        'cost_center' => 'DTH CT1',
//        'genre_id' => $this->genre->id,
//        'assigned_user_ids' => [$this->assigned_user->id],
//        'assigned_departments' => [$this->department],
//    ]);
//
//    $this->assertDatabaseHas('projects', [
//        'name' => 'TestProject',
//        'description' => 'a description',
//        'number_of_participants' => '1000',
//    ]);
//
//    $project = Project::where('name', 'TestProject')->first();
//
//    $this->assertDatabaseHas('project_user', [
//        'project_id' => $project->id,
//        'user_id' => $this->assigned_user->id,
//    ]);
//
//    $this->assertDatabaseHas('department_project', [
//        'project_id' => $project->id,
//        'department_id' => $this->department->id
//    ]);
//});

test('users without the permission cant create projects', function () {

    $this->actingAs($this->auth_user);

    $this->post('/projects', [
        'name' => 'TestProject',
        'assigned_users' => [$this->assigned_user],
        'assigned_departments' => [$this->department]
    ])->assertStatus(403);
});

//Tests can currently not work

//test('users can only view projects they are assigned to', function () {
//
//    $this->department->users()->attach($this->auth_user);
//    $this->auth_user->departments()->attach($this->department);
//
//    $this->project->departments()->attach($this->department);
//    $this->project->users()->attach($this->auth_user);
//    $this->department->projects()->attach($this->project);
//
//    $comment = Comment::factory()->create([
//       'project_id' => $this->project->id,
//       'user_id' => $this->auth_user
//    ]);
//
//    $checklist = Checklist::factory()->create();
//    $event = Event::factory()->create([
//        'project_id' => $this->project->id
//    ]);
//    $this->project->checklists()->save($checklist);
//    $this->auth_user->private_checklists()->save($checklist);
//
//    $this->actingAs($this->auth_user);
//
//    $response = $this->get("/projects/{$this->project->id}");
//    $response->assertInertia(fn(Assert $page) => $page
//            ->component('Projects/Show')
//            ->has('project.events.0')
//            ->has('project.comments.0', fn(Assert $page) => $page
//                ->hasAll(['id','text', 'created_at', 'user'])
//            )
//        );
//
//    $response->assertRedirect();
//    $response->assertStatus(200);
//});

test('users with the permission can update projects and change the role of assigned users', function () {

    $this->auth_user->givePermissionTo(\Artwork\Modules\Permission\Enums\PermissionEnum::PROJECT_MANAGEMENT->value);
    $this->actingAs($this->auth_user);

    $this->project->users()->attach($this->auth_user);

    $this->patch("/projects/{$this->project->id}", [
        'name' => 'TestProject',
        'description' => 'a description',
        'number_of_participants' => '1000',
        'assigned_user_ids' => [$this->auth_user->id => ['is_admin' => false]],
        'assigned_departments' => [$this->department]
    ]);

    $this->assertDatabaseHas('projects', [
        'name' => 'TestProject',
    ]);
});

test('users with the permission can duplicate projects', function() {

    $old_project = Project::factory()->create([
        'name' => 'TestProject',
        'number_of_participants' => '1000',
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

    $this->auth_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($this->auth_user);

    $res = $this->post("/projects/{$old_project->id}/duplicate");

    $this->assertDatabaseHas('projects', [
        'name' => '(Kopie) TestProject'
    ]);

    $new_project = Project::where('name', '(Kopie) TestProject')->first();

    $this->assertDatabaseHas('project_user', [
        'project_id' => $new_project->id,
        'user_id' => $this->assigned_user->id
    ]);


});

test('users with the permission can delete projects', function () {

    $this->auth_user->givePermissionTo('delete projects');
    $this->project->users()->attach($this->auth_user);
    $this->auth_user->projects()->attach($this->project);
    $this->actingAs($this->auth_user);

    $this->delete("/projects/{$this->project->id}");

    $this->assertDatabaseHas('projects', [
        'id' => $this->project->id,
        'deleted_at' => Date::now()
    ]);
});




