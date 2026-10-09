<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Key-Visual-Endpunkte (Hochladen, Herunterladen, Löschen):
 * Hochladen/Löschen brauchen ProjectPolicy::update, Herunterladen nur das Sichtrecht
 * (CanViewProject). Dateien liegen auf der Default-Disk unter public/keyVisual.
 */
final class ProjectKeyVisualTest extends FeatureTestCase
{
    #[Test]
    public function admin_can_upload_key_visual_and_old_file_is_replaced(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['key_visual_path' => 'old.png']);
        Storage::put('public/keyVisual/old.png', 'alt');

        $this->post(route('projects_key_visual.update', $project), [
            'keyVisual' => UploadedFile::fake()->image('plakat.png', 20, 20),
        ])->assertRedirect();

        $newPath = $project->fresh()->key_visual_path;
        $this->assertNotNull($newPath);
        $this->assertNotSame('old.png', $newPath);
        Storage::assertExists('public/keyVisual/' . $newPath);
        Storage::assertMissing('public/keyVisual/old.png');
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => $project->getMorphClass(),
            'subject_id' => $project->id,
        ]);
    }

    #[Test]
    public function upload_without_file_keeps_existing_key_visual(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['key_visual_path' => 'bleibt.png']);

        $this->post(route('projects_key_visual.update', $project), [])->assertRedirect();

        $this->assertSame('bleibt.png', $project->fresh()->key_visual_path);
    }

    #[Test]
    public function user_without_write_access_cannot_upload_key_visual(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $project = Project::factory()->create();

        $this->post(route('projects_key_visual.update', $project), [
            'keyVisual' => UploadedFile::fake()->image('plakat.png', 20, 20),
        ])->assertForbidden();

        $this->assertNull($project->fresh()->key_visual_path);
    }

    #[Test]
    public function write_team_member_can_delete_key_visual(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create(['key_visual_path' => 'kv.png']);
        $project->users()->attach($user->id, ['can_write' => true]);
        Storage::put('public/keyVisual/kv.png', 'bild');

        $this->delete(route('project.delete.keyVisual', $project))->assertOk();

        $this->assertNull($project->fresh()->key_visual_path);
        Storage::assertMissing('public/keyVisual/kv.png');
    }

    #[Test]
    public function user_without_write_access_cannot_delete_key_visual(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['key_visual_path' => 'kv.png']);
        Storage::put('public/keyVisual/kv.png', 'bild');

        $this->delete(route('project.delete.keyVisual', $project))->assertForbidden();

        $this->assertSame('kv.png', $project->fresh()->key_visual_path);
        Storage::assertExists('public/keyVisual/kv.png');
    }

    #[Test]
    public function team_member_can_download_key_visual(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create(['key_visual_path' => 'kv.png']);
        $project->users()->attach($user->id);
        Storage::put('public/keyVisual/kv.png', 'bild');

        $response = $this->get(route('project.download.keyVisual', $project));

        $response->assertOk();
        $response->assertDownload('kv.png');
    }

    #[Test]
    public function outsider_is_redirected_back_when_downloading_key_visual(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['key_visual_path' => 'kv.png']);
        Storage::put('public/keyVisual/kv.png', 'bild');

        $this->get(route('project.download.keyVisual', $project))->assertRedirect();
        $this->getJson(route('project.download.keyVisual', $project))->assertForbidden();
    }

    #[Test]
    public function download_without_key_visual_is_not_found(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['key_visual_path' => null]);

        $this->get(route('project.download.keyVisual', $project))->assertNotFound();
    }
}
