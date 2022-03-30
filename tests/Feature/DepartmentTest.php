<?php

use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function() {

    $this->auth_user = User::factory()->create();

});

test('authorized users can view departments', function () {

    for ($i = 0; $i < 10; $i++) {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $department->users()->attach($user->id);
        $user->departments()->attach($department->id);
    }

    $this->auth_user->givePermissionTo('view departments');

    $this->actingAs($this->auth_user);

    $response = $this->get('/departments')
        ->assertInertia(fn(Assert $page) => $page
            ->component('DepartmentManagement')
            ->has('departments.data', 10)
            ->has('users')
            ->has('departments.data.0', fn(Assert $page) => $page
                ->hasAll(['id','name', 'users', 'logo_url'])
            )
            ->has('departments.data.0.users.0', fn(Assert $page) => $page
                ->hasAll('id', 'first_name','last_name', 'email', 'profile_photo_url')
            )
            ->where('departments.per_page', 10)
        );

    $response->assertStatus(200);
});

test('authorized users can open the page to create new departments', function () {

    $this->auth_user->givePermissionTo('create departments');

    $this->actingAs($this->auth_user);

    $response = $this->get('/departments/create')
        ->assertInertia(fn(Assert $page) => $page
            ->component('Departments/Create')
        );

    $response->assertStatus(200);

});

test('authorized users can create new departments', function() {

    $this->auth_user->givePermissionTo('create departments', 'update users');

    $this->actingAs($this->auth_user);

    $user = User::factory()->create(['first_name' => 'TestName']);

    $this->post('/departments', [
        'name' => 'Department 1',
        'assigned_users' => [$user]
    ]);

    $this->assertDatabaseHas('departments', [
        'name' => 'Department 1'
    ]);

    $department = Department::where('name', 'Department 1')->first();

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user->id
    ]);

});

test('authorized users can view a single department', function() {

    $this->auth_user->givePermissionTo('view departments');

    $this->actingAs($this->auth_user);

    $department = Department::factory()->create();
    $user = User::factory()->create();

    $department->users()->attach($user->id);
    $user->departments()->attach($department->id);

    $response = $this->get("/departments/{$department->id}")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Departments/Show')
            ->has('department', fn(Assert $page) => $page
                ->hasAll(['id','name', 'users', 'logo_url'])
            )
            ->has('department.users.0', fn(Assert $page) => $page
                ->hasAll('id', 'first_name','last_name', 'email', 'profile_photo_url')
            )
        );

    $response->assertStatus(200);


});

test('authorized users can open the form to update a single department', function() {

    $this->auth_user->givePermissionTo('update departments');

    $this->actingAs($this->auth_user);

    $department = Department::factory()->create();
    $user = User::factory()->create();

    $department->users()->attach($user->id);
    $user->departments()->attach($department->id);

    $response = $this->get("/departments/{$department->id}/edit")
        ->assertInertia(fn(Assert $page) => $page
            ->component('Departments/Edit')
            ->has('users')
            ->has('department', fn(Assert $page) => $page
                ->hasAll(['id','name', 'users', 'logo_url'])
            )
            ->has('department.users.0', fn(Assert $page) => $page
                ->hasAll('id', 'first_name','last_name', 'email', 'profile_photo_url')
            )
        );

    $response->assertStatus(200);

});

test('authorized users can update a departments name and assigned users', function() {

    $this->auth_user->givePermissionTo('update departments', 'update users');

    $this->actingAs($this->auth_user);

    $department = Department::factory()->create();
    $user_1 = User::factory()->create(['first_name' => 'TestName1']);

    $department->users()->attach($user_1->id);
    $user_1->departments()->attach($department->id);

    $user_2 = User::factory()->create(['first_name' => 'TestName2']);

    $this->patch("departments/{$department->id}", [
        'name' => 'NewName',
        'assigned_users' => [
            $user_2
        ]
    ]);

    $this->assertDatabaseHas('departments', [
        'name' => 'NewName'
    ]);

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user_2->id
    ]);

});

test('authorized users can delete a department', function() {

    $this->auth_user->givePermissionTo('delete departments');
    $this->actingAs($this->auth_user);

    $department = Department::factory()->create();
    $user_1 = User::factory()->create();

    $department->users()->attach($user_1->id);
    $user_1->departments()->attach($department->id);

    $this->delete("departments/{$department->id}");

    $this->assertDatabaseMissing('departments', [
        'id' => $department->id
    ]);

});
