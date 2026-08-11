<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    public function user_downloads_file_as_attachment_by_default(): void
    {
        $user = $this->adminUser();
        $file = $this->createAccessibleProjectFile($user);

        $response = $this->actingAs($user)->get(route('download_file', $file));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition', '');

        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('doc.pdf', $contentDisposition);
    }

    #[Test]
    public function user_can_view_file_inline_for_printing(): void
    {
        $user = $this->adminUser();
        $file = $this->createAccessibleProjectFile($user);

        $response = $this->actingAs($user)->get(route('download_file', [
            'project_file' => $file,
            'inline' => true,
        ]));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition', '');

        $this->assertStringContainsString('inline', $contentDisposition);
        $this->assertStringContainsString('doc.pdf', $contentDisposition);
    }

    #[Test]
    public function inline_request_downloads_unsupported_file_types_as_attachment(): void
    {
        $user = $this->adminUser();
        $file = $this->createAccessibleProjectFile($user, 'page.html', 'abc.html', '<html></html>');

        $response = $this->actingAs($user)->get(route('download_file', [
            'project_file' => $file,
            'inline' => true,
        ]));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition', '');

        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('page.html', $contentDisposition);
    }

    #[Test]
    public function stored_file_name_is_hashed_while_the_display_name_stays_raw(): void
    {
        $user = $this->adminUser();
        $project = Project::factory()->create();
        $originalName = 'Mein broken°^„Filename “quoted” 12:30.pdf';

        $this->actingAs($user)
            ->post(route('project_files.store', $project), [
                'file' => UploadedFile::fake()->create($originalName, 10, 'application/pdf'),
            ]);

        $projectFile = ProjectFile::query()->firstOrFail();

        $this->assertSame($originalName, $projectFile->name);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.[a-z0-9]+$/', $projectFile->basename);
        Storage::assertExists('project_files/' . $projectFile->basename);
    }

    #[Test]
    public function a_hashed_file_is_downloaded_under_its_original_name(): void
    {
        $user = $this->adminUser();
        $project = Project::factory()->create();
        $originalName = 'Jahresbericht “2026”.pdf';

        $this->actingAs($user)
            ->post(route('project_files.store', $project), [
                'file' => UploadedFile::fake()->create($originalName, 10, 'application/pdf'),
            ]);

        $projectFile = ProjectFile::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('download_file', $projectFile));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition', '');

        $this->assertStringNotContainsString($projectFile->basename, $contentDisposition);
        $this->assertStringContainsString('Jahresbericht', $contentDisposition);
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

    private function createAccessibleProjectFile(
        User $user,
        string $name = 'doc.pdf',
        string $basename = 'abc.pdf',
        string $contents = '%PDF-1.4 test'
    ): ProjectFile {
        $project = Project::factory()->create();
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => $name,
            'basename' => $basename,
        ]);

        $file->accessingUsers()->attach($user->id);
        Storage::put('project_files/' . $basename, $contents);

        return $file;
    }
}
