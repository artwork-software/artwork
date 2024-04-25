<?php

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function (): void {
    EventFacade::fake();
});

test('users can update update other users', function (): void {
    $user = User::factory()->create();
    $department = Department::factory()->create();

    $user_to_edit = User::factory()->create();
    $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($user);

    $response = $this->patch(route('user.update', [$user_to_edit->id]), [
        "first_name" => "Benjamin",
        "last_name" => "Willems",
        "position" => "CEO",
        "phone_number" => "1337",
        "permissions" => [PermissionEnum::ROOM_UPDATE->value],
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

    $this->assertDatabaseHas('department_user', [
        'department_id' => $department->id,
        'user_id' => $user_to_edit->id
    ]);
});

test('user can update another users permissions and roles', function (): void {
    $user = User::factory()->create();
    $user_to_edit = User::factory()->create();
    $user->assignRole(RoleEnum::ARTWORK_ADMIN->value);
    $this->actingAs($user);

    $permissionsToGrant = [];
    foreach (PermissionEnum::cases() as $permissionNameEnum) {
        $permissionsToGrant[] = $permissionNameEnum->value;
    }
    $permissionNotToGrant = array_pop($permissionsToGrant);

    $response = $this->patch(route('user.update.permissions-and-roles', [$user_to_edit->id]), [
        "permissions" => $permissionsToGrant,
        "roles" => [RoleEnum::ARTWORK_ADMIN->value]
    ]);

    $response->assertRedirect();

    $updated_user = User::where('id', $user_to_edit->id)->first();

    foreach ($permissionsToGrant as $grantedPermission) {
        $this->assertTrue($updated_user->hasPermissionTo($grantedPermission));
    }

    $this->assertFalse($updated_user->hasPermissionTo($permissionNotToGrant));

    $this->assertTrue($updated_user->hasRole(RoleEnum::ARTWORK_ADMIN->value));
});

test('users cannot update users without permission', function (): void {

    $user = User::factory()->create([
        'first_name' => 'updater user'
    ]);
    $user->revokePermissionTo(PermissionEnum::TEAM_UPDATE->value);

    $user_to_edit = User::factory()->create([
        'first_name' => 'updated user'
    ]);

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

test('users can delete other users', function (): void {

    $user = $this->adminUser();

    $user_to_edit = User::factory()->create();
    $this->actingAs($user);

    $response = $this->delete("/users/{$user_to_edit->id}");

    $response->assertRedirect();

    $this->assertDatabaseMissing('users', [
        "id" => $user_to_edit->id,
    ]);
});

test('users can delete their own accounts', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->delete("/users/{$user->id}");

    $response->assertStatus(302);

    $this->assertDatabaseMissing('users', [
        "id" => $user->id,
    ]);
});

test('consultants cannot delete any client', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $user_to_edit = User::factory()->create();

    $this->delete("/users/{$user_to_edit->id}")->assertStatus(403);
});
