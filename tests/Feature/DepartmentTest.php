<?php

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\User\Models\User;

beforeEach(function() {

    $this->auth_user = User::factory()->create();
    $this->auth_user->assignRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($this->auth_user);
});

test('authorized users can view departments', function () {

    for ($i = 0; $i < 10; $i++) {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $department->users()->attach($user->id);
        $user->departments()->attach($department->id);
    }

    $response = $this->get('/departments');

    $response->assertStatus(200);
});

test('authorized users can create new departments', function() {

    $user = User::factory()->create(['first_name' => 'TestName']);

    $this->post('/departments', [
        'name' => 'Department 1',
        'svg_name' => 'logo.svg',
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

    $department = Department::factory()->create();
    $user = User::factory()->create();

    $department->users()->attach($user->id);
    $user->departments()->attach($department->id);

    $response = $this->get("/departments/{$department->id}");

    $response->assertStatus(200);
});

test('authorized users can open the form to update a single department', function() {

    $department = Department::factory()->create();
    $user = User::factory()->create();

    $department->users()->attach($user->id);
    $user->departments()->attach($department->id);

    $response = $this->get("/departments/{$department->id}/edit");

    $response->assertStatus(200);

});

test('authorized users can update a departments name and assigned users', function() {

    \Illuminate\Support\Facades\Event::fake();
    $department = Department::factory()->create();
    $user_1 = User::factory()->create(['first_name' => 'TestName1']);

    $department->users()->attach($user_1->id);
    $user_1->departments()->attach($department->id);

    $user_2 = User::factory()->create(['first_name' => 'TestName2']);

    $response = $this->patch("departments/{$department->id}", [
        'name' => 'NewName',
        'svg_name' => 'lel',
        'users' => [
            $user_2
        ]
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('departments', [
        'name' => 'NewName'
    ]);

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user_2->id
    ]);

});

test('authorized users can delete a department', function() {

    $department = Department::factory()->create();
    $user_1 = User::factory()->create();

    $department->users()->attach($user_1->id);
    $user_1->departments()->attach($department->id);

    $this->delete("departments/{$department->id}");

    $this->assertDatabaseMissing('departments', [
        'id' => $department->id
    ]);

});
