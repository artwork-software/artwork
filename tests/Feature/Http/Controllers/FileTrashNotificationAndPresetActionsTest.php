<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\PermissionPreset;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Room\Models\RoomFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für endgültiges Löschen von Projekt- und Raumdateien, das Löschen globaler
 * Benachrichtigungen und das Bearbeiten von Rechte-Presets.
 */
final class FileTrashNotificationAndPresetActionsTest extends FeatureTestCase
{
    #[Test]
    public function force_deleting_a_trashed_project_file_removes_record_and_stored_file(): void
    {
        $this->actingAsAdmin();
        $file = ProjectFile::factory()->create();
        Storage::disk('local')->put($file->storagePath(), 'pdf');
        $file->delete();

        $this->from('/trash')->delete('/project_files/' . $file->id . '/force_delete')->assertRedirect('/trash');

        $this->assertDatabaseMissing('project_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($file->storagePath());
    }

    #[Test]
    public function project_files_outside_the_trash_cannot_be_force_deleted(): void
    {
        $this->actingAsAdmin();
        $file = ProjectFile::factory()->create();
        Storage::disk('local')->put($file->storagePath(), 'pdf');

        $this->delete('/project_files/' . $file->id . '/force_delete')->assertNotFound();
        $this->delete('/project_files/99999999/force_delete')->assertNotFound();

        $this->assertNotSoftDeleted($file);
        Storage::disk('local')->assertExists($file->storagePath());
    }

    #[Test]
    public function destroying_a_room_file_moves_it_to_the_trash_and_keeps_the_stored_file(): void
    {
        $this->actingAsAdmin();
        $file = RoomFile::factory()->create();
        Storage::disk('local')->put('room_files/' . $file->basename, 'pdf');

        $this->from('/room')->delete('/room_files/' . $file->id)->assertRedirect('/room');

        $this->assertSoftDeleted('room_files', ['id' => $file->id]);
        Storage::disk('local')->assertExists('room_files/' . $file->basename);
    }

    #[Test]
    public function force_deleting_a_trashed_room_file_removes_record_and_stored_file(): void
    {
        $this->actingAsAdmin();
        $file = RoomFile::factory()->create();
        Storage::disk('local')->put('room_files/' . $file->basename, 'pdf');
        $file->delete();

        $this->from('/trash')->delete('/room_files/' . $file->id . '/force_delete')->assertRedirect('/trash');

        $this->assertDatabaseMissing('room_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing('room_files/' . $file->basename);
    }

    #[Test]
    public function room_files_outside_the_trash_cannot_be_force_deleted(): void
    {
        $this->actingAsAdmin();
        $file = RoomFile::factory()->create();

        $this->delete('/room_files/' . $file->id . '/force_delete')->assertNotFound();

        $this->assertNotSoftDeleted('room_files', ['id' => $file->id]);
    }

    #[Test]
    public function destroying_a_global_notification_removes_it_and_its_image(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');
        Storage::disk('public')->put('notificationImage/banner.png', 'png');
        $notification = GlobalNotification::factory()->create(['image_name' => 'notificationImage/banner.png']);

        $this->from('/dashboard')
            ->delete(route('global_notification.destroy', $notification))
            ->assertRedirect('/dashboard');

        $this->assertModelMissing($notification);
        Storage::disk('public')->assertMissing('notificationImage/banner.png');
    }

    #[Test]
    public function destroying_a_global_notification_never_deletes_files_outside_the_image_folder(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');
        Storage::disk('public')->put('other/keep.png', 'png');
        $notification = GlobalNotification::factory()->create(['image_name' => 'notificationImage/../other/keep.png']);

        $this->delete(route('global_notification.destroy', $notification))->assertRedirect();

        $this->assertModelMissing($notification);
        Storage::disk('public')->assertExists('other/keep.png');
    }

    #[Test]
    public function destroying_a_global_notification_without_image_works(): void
    {
        $this->actingAsAdmin();
        $notification = GlobalNotification::factory()->create(['image_name' => '']);

        $this->delete(route('global_notification.destroy', $notification))->assertRedirect();

        $this->assertModelMissing($notification);
    }

    #[Test]
    public function updating_a_permission_preset_renames_it_and_keeps_only_known_permissions(): void
    {
        $this->actingAsAdmin();
        $preset = PermissionPreset::factory()->create(['name' => 'Alt', 'permissions' => []]);

        $this->from('/presets')->patch(route('permission-presets.update', $preset), [
            'name' => 'Technik',
            'permissions' => [PermissionEnum::PROJECT_SETTINGS_UPDATE->value, 'gibt es nicht'],
        ])->assertRedirect('/presets')->assertSessionHas('success');

        $preset->refresh();
        $this->assertSame('Technik', $preset->name);
        $this->assertContains(PermissionEnum::PROJECT_SETTINGS_UPDATE->value, $preset->permissions);
        $this->assertNotContains('gibt es nicht', $preset->permissions);
    }

    #[Test]
    public function a_permission_preset_may_keep_its_own_name_but_not_take_another_ones(): void
    {
        $this->actingAsAdmin();
        PermissionPreset::factory()->create(['name' => 'Vergeben']);
        $preset = PermissionPreset::factory()->create(['name' => 'Eigener']);

        $this->patch(route('permission-presets.update', $preset), ['name' => 'Eigener', 'permissions' => []])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->patchJson(route('permission-presets.update', $preset), ['name' => 'Vergeben', 'permissions' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame('Eigener', $preset->fresh()->name);
    }

    #[Test]
    public function a_permission_preset_needs_a_name(): void
    {
        $this->actingAsAdmin();
        $preset = PermissionPreset::factory()->create(['name' => 'Alt']);

        $this->patchJson(route('permission-presets.update', $preset), ['name' => '', 'permissions' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->patchJson(route('permission-presets.update', $preset), ['name' => str_repeat('x', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame('Alt', $preset->fresh()->name);
    }
}
