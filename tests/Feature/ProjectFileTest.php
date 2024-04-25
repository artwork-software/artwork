<?php

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_user = User::factory()->create();

    $this->project = Project::factory()->create();

    $this->project_file = ProjectFile::factory()->create(['project_id' => $this->project->id]);

    $this->project->users()->attach($this->assigned_user);

});

test('attached users can upload files to a project', function () {

    $this->actingAs($this->assigned_user);

    $this->post("/projects/{$this->project->id}/files", [
        'file' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

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
