<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserPhotoAuthorizationTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    #[Test]
    public function guest_cannot_update_or_delete_user_photo(): void
    {
        $user = User::factory()->create();

        $this->post(route('user.update.photo', $user), ['photo' => UploadedFile::fake()->create('avatar.jpg')])
            ->assertRedirect(route('login'));
        $this->delete(route('user.delete.photo', $user))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function user_without_permission_cannot_update_other_users_photo(): void
    {
        $this->actingAs(User::factory()->create());
        $other = User::factory()->create();

        $this->post(route('user.update.photo', $other), ['photo' => UploadedFile::fake()->create('avatar.jpg')])
            ->assertForbidden();

        $this->assertNull($other->refresh()->profile_photo_path);
    }

    #[Test]
    public function user_without_permission_cannot_delete_other_users_photo(): void
    {
        $this->actingAs(User::factory()->create());
        $other = User::factory()->create();
        $other->updateProfilePhoto(UploadedFile::fake()->create('avatar.jpg'));

        $this->delete(route('user.delete.photo', $other))
            ->assertForbidden();

        $this->assertNotNull($other->refresh()->profile_photo_path);
    }

    #[Test]
    public function user_can_update_and_delete_own_photo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('user.update.photo', $user), ['photo' => UploadedFile::fake()->create('avatar.jpg')])
            ->assertOk();
        $this->assertNotNull($user->refresh()->profile_photo_path);

        $this->delete(route('user.delete.photo', $user))
            ->assertOk();
        $this->assertNull($user->refresh()->profile_photo_path);
    }

    #[Test]
    public function hr_administrator_can_update_and_delete_other_users_photo(): void
    {
        // Profilbearbeitung fremder Personen liegt seit dem Rechte-Katalog bei "Personalverwaltung"
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $other = User::factory()->create();

        $this->post(route('user.update.photo', $other), ['photo' => UploadedFile::fake()->create('avatar.jpg')])
            ->assertOk();
        $this->assertNotNull($other->refresh()->profile_photo_path);

        $this->delete(route('user.delete.photo', $other))
            ->assertOk();
        $this->assertNull($other->refresh()->profile_photo_path);
    }

    #[Test]
    public function hr_administrator_cannot_update_or_delete_an_admins_photo(): void
    {
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        Role::query()->firstOrCreate(['name' => RoleEnum::ARTWORK_ADMIN->value, 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ARTWORK_ADMIN->value);
        $admin->updateProfilePhoto(UploadedFile::fake()->create('avatar.jpg'));
        $photoPath = $admin->refresh()->profile_photo_path;

        $this->post(route('user.update.photo', $admin), ['photo' => UploadedFile::fake()->create('other.jpg')])
            ->assertForbidden();
        $this->delete(route('user.delete.photo', $admin))
            ->assertForbidden();

        $this->assertSame($photoPath, $admin->refresh()->profile_photo_path);
    }

    #[Test]
    public function artwork_admin_can_update_and_delete_other_users_photo(): void
    {
        $this->actingAsAdmin();
        $other = User::factory()->create();

        $this->post(route('user.update.photo', $other), ['photo' => UploadedFile::fake()->create('avatar.jpg')])
            ->assertOk();
        $this->assertNotNull($other->refresh()->profile_photo_path);

        $this->delete(route('user.delete.photo', $other))
            ->assertOk();
        $this->assertNull($other->refresh()->profile_photo_path);
    }
}
