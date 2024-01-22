<?php

use App\Models\User;
use Artwork\Modules\Department\Models\Department;
use Inertia\Testing\AssertableInertia;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function() {
    EventFacade::fake();
});

test('users can update update other users', function () {

    EventFacade::fake();
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
