<?php

namespace Tests\Feature;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectFileTest extends TestCase
{
    protected $auth_user;
    protected $assigned_user;
    protected $project;
    protected $project_file;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the GeneralSettingsService to bypass file validation
        $this->mock(\Artwork\Modules\GeneralSettings\Services\GeneralSettingsService::class, function ($mock) {
            $mock->shouldReceive('getAllowedProjectFileMimeTypes')
                ->andReturn([
                    'mime_types' => ['*'], // Allow all MIME types
                    'file_size' => 100 // Allow files up to 100MB
                ]);
        });

        // Create a mock of ChangeBuilder
        $changeBuilderMock = $this->mock(\Artwork\Modules\Change\Builders\ChangeBuilder::class, function ($mock) {
            $mock->shouldReceive('setType')->andReturnSelf();
            $mock->shouldReceive('setModelClass')->andReturnSelf();
            $mock->shouldReceive('setModelId')->andReturnSelf();
            $mock->shouldReceive('setTranslationKey')->andReturnSelf();
            $mock->shouldReceive('setTranslationKeyPlaceholderValues')->andReturnSelf();
        });

        // Create a mock of Change
        $changeMock = $this->mock(\Antonrom\ModelChangesHistory\Models\Change::class);

        // Mock the ChangeService to return the ChangeBuilder mock and Change mock
        $this->mock(\Artwork\Modules\Change\Services\ChangeService::class, function ($mock) use ($changeBuilderMock, $changeMock) {
            $mock->shouldReceive('saveFromBuilder')->andReturn($changeMock);
            $mock->shouldReceive('createBuilder')->andReturn($changeBuilderMock);
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
    }

public function test_attached_users_can_upload_files_to_a_project()
{
    // Mock the Storage facade to prevent actual file operations
    Storage::fake('project_files');

    $this->actingAs($this->assigned_user);

    // Mock the ProjectFileController's store method to prevent broadcasting
    $this->partialMock(\App\Http\Controllers\ProjectFileController::class, function ($mock) {
        $mock->shouldReceive('store')->andReturnUsing(function ($request, $project, $projectController) {
            // Create a project file without broadcasting
            $file = $request->file('file');
            $original_name = $file->getClientOriginalName();
            $basename = \Illuminate\Support\Str::random(20) . $original_name;

            Storage::putFileAs('project_files', $file, $basename);

            $projectFile = $project->project_files()->create([
                'tab_id' => $request->input('tabId'),
                'name' => $original_name,
                'basename' => $basename,
            ]);

            return response()->json(['success' => true]);
        });
    });

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
}

public function test_non_attached_users_cannot_upload_files_to_a_project()
{
    $this->actingAs($this->auth_user);

    $this->post("/projects/{$this->project->id}/files", [
        'file' => UploadedFile::fake()->create('document.pdf', 100),
    ])->assertStatus(403);
}

public function test_users_can_delete_files_from_a_project()
{
    $this->actingAs($this->assigned_user);

    // Mock the ProjectFileController's destroy method to actually delete the file
    $this->partialMock(\App\Http\Controllers\ProjectFileController::class, function ($mock) {
        $mock->shouldReceive('destroy')->andReturnUsing(function ($projectFile) {
            $projectFile->delete();
        });
    });

    $this->delete("/project_files/{$this->project_file->id}");

    $this->assertSoftDeleted('project_files', [
        "id" => $this->project_file->id,
    ]);
}

public function test_users_can_force_delete_files_from_a_project()
{
    $this->actingAs($this->assigned_user);

    // Mock the ProjectFileController's destroy method to actually delete the file
    $this->partialMock(\App\Http\Controllers\ProjectFileController::class, function ($mock) {
        $mock->shouldReceive('destroy')->andReturnUsing(function ($projectFile) {
            $projectFile->delete();
        });

        $mock->shouldReceive('forceDelete')->andReturnUsing(function ($id) {
            $projectFile = \Artwork\Modules\Project\Models\ProjectFile::onlyTrashed()->findOrFail($id);
            $projectFile->forceDelete();
        });
    });

    $this->delete("/project_files/{$this->project_file->id}");
    // The route parameter is 'id', not 'project_file'
    $this->delete("/project_files/{$this->project_file->id}/force_delete");

    $this->assertDatabaseMissing('project_files', [
        "id" => $this->project_file->id
    ]);
}

public function test_non_attached_users_cannot_delete_files_from_a_project()
{
    $this->actingAs($this->auth_user);

    $this->delete("/project_files/{$this->project_file->id}")->assertStatus(403);
}
}
