<?php

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Mock the GeneralSettingsService to bypass file validation
    $this->mock(\Artwork\Modules\GeneralSettings\Services\GeneralSettingsService::class, function ($mock) {
        $mock->shouldReceive('getAllowedProjectFileMimeTypes')
            ->andReturn([
                'mime_types' => ['*'], // Allow all MIME types
                'file_size' => 100 // Allow files up to 100MB
            ]);
    });

    // Mock the ChangeService
    $this->mock(\Artwork\Modules\Change\Services\ChangeService::class, function ($mock) {
        $mock->shouldReceive('saveFromBuilder')->andReturn(null);
        $mock->shouldReceive('createBuilder')->andReturnSelf();
        $mock->shouldReceive('setType')->andReturnSelf();
        $mock->shouldReceive('setModelClass')->andReturnSelf();
        $mock->shouldReceive('setModelId')->andReturnSelf();
        $mock->shouldReceive('setTranslationKey')->andReturnSelf();
        $mock->shouldReceive('setTranslationKeyPlaceholderValues')->andReturnSelf();
    });

    // Mock the NotificationService
    $this->mock(\Artwork\Modules\Notification\Services\NotificationService::class, function ($mock) {
        $mock->shouldReceive('setIcon')->andReturnSelf();
        $mock->shouldReceive('setPriority')->andReturnSelf();
        $mock->shouldReceive('setNotificationConstEnum')->andReturnSelf();
        $mock->shouldReceive('setProjectId')->andReturnSelf();
        $mock->shouldReceive('setTitle')->andReturnSelf();
        $mock->shouldReceive('setBroadcastMessage')->andReturnSelf();
        $mock->shouldReceive('setDescription')->andReturnSelf();
        $mock->shouldReceive('setNotificationTo')->andReturnSelf();
        $mock->shouldReceive('createNotification')->andReturnSelf();
    });

    // Mock the ProjectTabService
    $this->mock(\Artwork\Modules\ProjectTab\Services\ProjectTabService::class, function ($mock) {
        $mock->shouldReceive('getFirstProjectTabWithTypeIdOrFirstProjectTabId')->andReturn(1);
    });

    // Mock the ProjectController
    $this->mock(\App\Http\Controllers\ProjectController::class, function ($mock) {
        $mock->shouldReceive('setPublicChangesNotification')->andReturn(null);
    });

    $this->auth_user = User::factory()->create();

    $this->assigned_user = User::factory()->create();

    $this->project = Project::factory()->create();

    $this->project_file = ProjectFile::factory()->create(['project_id' => $this->project->id]);

    $this->project->users()->attach($this->assigned_user);
});

test('attached users can upload files to a project', function () {
    // Mock the Storage facade to prevent actual file operations
    Storage::fake('project_files');

    $this->actingAs($this->assigned_user);

    // Create a fake file with a specific MIME type
    $file = UploadedFile::fake()->create(
        'document.pdf',
        100,
        'application/pdf'
    );

    // Make the request with the fake file
    $response = $this->post("/projects/{$this->project->id}/files", [
        'file' => $file,
    ]);

    // Assert that the response is successful
    $response->assertStatus(200);

    // Assert that the file was stored in the database
    $this->assertDatabaseHas('project_files', [
        'name' => 'document.pdf',
        'project_id' => $this->project->id
    ]);
});

test('non attached users cannot upload files to a project', function () {

    $this->actingAs($this->auth_user);

    $this->post("/projects/{$this->project->id}/files", [
        'file' => UploadedFile::fake()->create('document.pdf', 100),
    ])->assertStatus(403);

});

test('users can delete files from a project', function () {

    $this->actingAs($this->assigned_user);

    $this->delete("/project_files/{$this->project_file->id}");

    $this->assertDatabaseHas('project_files', [
        "id" => $this->project_file->id,
        'deleted_at' => \Carbon\Carbon::now(),
    ]);
});

test('users can force delete files from a project', function () {

    $this->actingAs($this->assigned_user);

    $this->delete("/project_files/{$this->project_file->id}");
    $this->delete("/project_files/{$this->project_file->id}/force_delete");

    $this->assertDatabaseMissing('project_files', [
        "id" => $this->project_file->id
    ]);
});

test('non attached users cannot delete files from a project', function () {

    $this->actingAs($this->auth_user);

    $this->delete("/project_files/{$this->project_file->id}")->assertStatus(403);

});
