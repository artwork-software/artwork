<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectFileControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $project = Project::factory()->create();

        $this->post(route('project_files.store', $project), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_download(): void
    {
        $project = Project::factory()->create();
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => 'doc.pdf',
            'basename' => 'abc.pdf',
        ]);

        $this->get('/project_files/' . $file->id)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $project = Project::factory()->create();
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => 'doc.pdf',
            'basename' => 'abc.pdf',
        ]);

        $this->delete(route('project_files.destroy', $file))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_force_delete(): void
    {
        $project = Project::factory()->create();
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => 'doc.pdf',
            'basename' => 'abc.pdf',
        ]);
        $file->delete();

        $this->delete('/project_files/' . $file->id . '/force_delete')
            ->assertRedirect(route('login'));
    }
}
