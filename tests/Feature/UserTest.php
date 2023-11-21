<?php

use App\Models\User;
use Artwork\Modules\Department\Models\Department;
use Inertia\Testing\AssertableInertia;

test('users can view users if they have the right to', function () {

    $user = User::factory()->create();

    $user->assignRole('admin');

    $this->actingAs($user);

    $response = $this->get('/users')
        ->assertInertia(fn(AssertableInertia $page) => $page
            ->component('Users/Index')
            ->has('users.data', 2)
            ->has('users.data.0', fn(AssertableInertia $page) => $page
                ->hasAll([
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'position',
                        'business',
                        'departments'
                    ])->etc()
            )
            ->where('users.per_page', 15)
        );

    $response->assertStatus(200);

    $user->removeRole('admin');
    $user->givePermissionTo('view users');

    $response = $this->get('/users')
        ->assertInertia(fn(AssertableInertia $page) => $page
            ->component('Users/Index')
            ->has('users.data', 2)
            ->has('users.data.0', fn(AssertableInertia $page) => $page
                ->hasAll([
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'position',
                    'business',
                    'departments'
                ])->etc()
            )
            ->where('users.per_page', 15)
        );

    $response->assertStatus(200);
});

test('users cannot view all users without permission', function () {

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/users');

    $response->assertStatus(403);
});

test('users can update update other users', function () {

    $user = User::factory()->create();
    $department = Department::factory()->create();


    $user_to_edit = User::factory()->create();
    $user_to_edit->givePermissionTo('view users', 'update users');
    $user->assignRole('admin');
    $this->actingAs($user);

    $response = $this->patch("/users/{$user_to_edit->id}", [
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "business" => "DTH",
        "phone_number" => "1337",
        "permissions" => ['invite users'],
        "departments" => [$department]
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('users', [
        "id" => $user_to_edit->id,
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "business" => "DTH",
        "phone_number" => "1337",
        "description" => $user->description,
    ]);

    $updated_user = User::where('id', $user_to_edit->id)->first();

    $this->assertFalse($updated_user->hasAnyPermission('view users', 'update users'));

    $this->assertTrue($updated_user->hasPermissionTo('invite users'));

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user_to_edit->id
    ]);

    $user->removeRole('admin');
    $user->givePermissionTo('update users');
    $user->givePermissionTo('update departments');

    $response = $this->patch("/users/{$user_to_edit->id}", [
        "first_name" => "Miriam",
        "last_name" => "Seixas",
        "position" => "CEO",
        "business" => "DTH",
        "phone_number" => "1337",
        "description" => null,
        "departments" => [$department]
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('users', [
        "id" => $user_to_edit->id,
        "first_name" => "Miriam",
        "last_name" => "Seixas",
        "position" => "CEO",
        "business" => "DTH",
        "phone_number" => "1337",
        "description" => null,
    ]);

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user_to_edit->id
    ]);

});

test('users cannot update users without permission', function () {

    $user = User::factory()->create();

    $user_to_edit = User::factory()->create();

    $this->actingAs($user);

    $this->patch("/users/{$user_to_edit->id}", [
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "business" => "DTH",
        "phone_number" => "1337",
        "description" => "Description was changed"
    ])->assertForbidden();

});

test('users can delete other users', function () {

    $user = User::factory()->create();

    $user_to_edit = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $response = $this->delete("/users/{$user_to_edit->id}");

    $response->assertStatus(302);

    $this->assertDatabaseMissing('users', [
        "id" => $user_to_edit->id,
    ]);

    $user_to_edit = User::factory()->create();
    $user->removeRole('admin');
    $user->givePermissionTo('delete users');

    $response = $this->delete("/users/{$user_to_edit->id}");

    $response->assertStatus(302);

    $this->assertDatabaseMissing('users', [
        "id" => $user_to_edit->id,
    ]);

});

test('users can delete their own accounts', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->delete("/users/{$user->id}");

    $response->assertStatus(302);

    $this->assertDatabaseMissing('users', [
        "id" => $user->id,
    ]);

});

test('consultants cannot delete any client', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $user_to_edit = User::factory()->create();

    $this->delete("/users/{$user_to_edit->id}")->assertStatus(403);
});
