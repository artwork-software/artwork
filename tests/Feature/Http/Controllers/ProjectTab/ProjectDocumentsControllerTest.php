<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectDocumentsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_all_documents_tab(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.all-documents', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_all_documents_tab(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.all-documents', $project));

        $response->assertOk();
    }

    #[Test]
    public function missing_project_files_do_not_break_the_document_list(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => 'available.pdf',
            'basename' => 'available.pdf',
        ]);
        ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => 'missing.pdf',
            'basename' => 'missing.pdf',
        ]);
        Storage::put('project_files/available.pdf', str_repeat('a', 1024));

        $response = $this->getJson(route('projects.tabs.all-documents', $project));

        $response->assertOk()
            ->assertJsonCount(2, 'documents')
            ->assertJsonFragment([
                'name' => 'available.pdf',
                'file_size' => '1.00 Kb',
                'storage_available' => true,
            ])
            ->assertJsonFragment([
                'name' => 'missing.pdf',
                'file_size' => null,
                'storage_available' => false,
            ]);
    }

    #[Test]
    public function user_without_project_access_is_redirected_back_for_all_documents(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->get(route('projects.tabs.all-documents', $project));

        $response->assertStatus(302);
    }

    #[Test]
    public function admin_gets_not_found_for_unknown_component_in_documents(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.documents', [
            'project' => $project->id,
            'componentInTab' => 999999,
        ]));

        $response->assertNotFound();
    }
}
