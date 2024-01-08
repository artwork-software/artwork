<?php

use App\Models\User;
use Artwork\Modules\Department\Models\Department;
use Inertia\Testing\AssertableInertia;

/** @todo permission is always true */
//test('users can view users if they have the right to', function () {
//
//    $user = User::factory()->create();
//
//    $user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
//
//    $this->actingAs($user);
//
//    $response = $this->get('/users')
//        ->assertInertia(fn(AssertableInertia $page) => $page
//            ->component('Users/Index')
//            ->has('users.data', 2)
//            ->has('users.data.0', fn(AssertableInertia $page) => $page
//                ->hasAll([
//                        'first_name',
//                        'last_name',
//                        'email',
//                        'phone_number',
//                        'position',
//                        'business',
//                        'departments'
//                    ])->etc()
//            )
//            ->where('users.per_page', 15)
//        );
//
//    $response->assertStatus(200);
//
//    $user->removeRole('admin');
//
//    $response = $this->get('/users')
//        ->assertInertia(fn(AssertableInertia $page) => $page
//            ->component('Users/Index')
//            ->has('users.data', 2)
//            ->has('users.data.0', fn(AssertableInertia $page) => $page
//                ->hasAll([
//                    'first_name',
//                    'last_name',
//                    'email',
//                    'phone_number',
//                    'position',
//                    'business',
//                    'departments'
//                ])->etc()
//            )
//            ->where('users.per_page', 15)
//        );
//
//    $response->assertStatus(200);
//});

/**
 * @todo I think this test is obsolete
 */

//
//test('users cannot view all users without permission', function () {
//
//    $user = User::factory()->create();
//    $this->actingAs($user);
//
//    $response = $this->get('/users');
//
//    $response->assertStatus(403);
//});

test('users can update update other users', function () {

    $user = User::factory()->create();
    $department = Department::factory()->create();

    $user_to_edit = User::factory()->create();
    $user->assignRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
    $this->actingAs($user);

    $response = $this->patch(route('user.update', [$user_to_edit->id]), [
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "phone_number" => "1337",
        "permissions" => [\App\Enums\PermissionNameEnum::ROOM_UPDATE->value],
        "departments" => [$department]
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('users', [
        "id" => $user_to_edit->id,
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "phone_number" => "1337",
        "description" => $user->description,
    ]);

    $updated_user = User::where('id', $user_to_edit->id)->first();

    $this->assertFalse($updated_user->hasAnyPermission('view users', 'update users'));

    $this->assertTrue($updated_user->hasPermissionTo(\App\Enums\PermissionNameEnum::ROOM_UPDATE->value));

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user_to_edit->id
    ]);

    /** @todo App\Enums\PermissionNameEnum::USER_UPDATE does not exist in seeder */

//    $user->removeRole(\App\Enums\RoleNameEnum::ARTWORK_ADMIN->value);
//    $user->givePermissionTo(\App\Enums\PermissionNameEnum::USER_UPDATE->value, \App\Enums\PermissionNameEnum::DEPARTMENT_UPDATE->value);
//
//    $response = $this->patch(route('user.update', [$user_to_edit->id]), [
//        "first_name" => "Miriam",
//        "last_name" => "Seixas",
//        "position" => "CEO",
//        "phone_number" => "1337",
//        "description" => null,
//        "departments" => [$department]
//    ]);
//
//    $response->assertRedirect();
//
//    $this->assertDatabaseHas('users', [
//        "id" => $user_to_edit->id,
//        "first_name" => "Miriam",
//        "last_name" => "Seixas",
//        "position" => "CEO",
//        "phone_number" => "1337",
//        "description" => null,
//    ]);
//
//    $this->assertDatabaseHas('department_user', [
//        'department_id' => $department->id,
//        'user_id' => $user_to_edit->id
//    ]);

});

test('users cannot update users without permission', function () {

    $user = User::factory()->create();

    $user_to_edit = User::factory()->create();

    $this->actingAs($user);

    $this->patch(route('user.update', [$user_to_edit->id]), [
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "business" => "DTH",
        "phone_number" => "1337",
        "description" => "Description was changed"
    ])->assertForbidden();

});

test('users can delete other users', function () {

    $user = $this->adminUser();

    $user_to_edit = User::factory()->create();
    $this->actingAs($user);

    $response = $this->delete("/users/{$user_to_edit->id}");

    $response->assertRedirect();

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
