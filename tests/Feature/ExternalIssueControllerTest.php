<?php

namespace Tests\Feature;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class ExternalIssueControllerTest extends TestCase
{
    public function testStoreRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'name' => 'Test External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'notes' => 'Test notes',
            'special_items_done' => false,
            'material_value' => 100.00,
            'external_name' => 'External Company'
        ];

        $response = $this->post("/extern-issue-of-material/store", $data);

        $response->assertStatus(302)
            ->assertRedirect(route('extern-issue-of-material.index'));
    }

    public function testUpdateRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $externalIssue = ExternalIssue::create([
            'name' => 'Test External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'notes' => 'Test notes',
            'special_items_done' => false,
            'issued_by_id' => $user->id,
            'material_value' => 100.00,
            'external_name' => 'External Company'
        ]);

        $data = [
            'name' => 'Updated External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-03',
            'notes' => 'Updated notes',
            'special_items_done' => false,
            'material_value' => 150.00,
            'external_name' => 'Updated External Company'
        ];

        $response = $this->patch("/extern-issue-of-material/{$externalIssue->id}/update", $data);

        $response->assertStatus(302)
            ->assertRedirect(route('extern-issue-of-material.index'));
    }

    public function testDestroyRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $externalIssue = ExternalIssue::create([
            'name' => 'Test External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'notes' => 'Test notes',
            'special_items_done' => false,
            'issued_by_id' => $user->id,
            'material_value' => 100.00,
            'external_name' => 'External Company'
        ]);

        $response = $this->delete("/extern-issue-of-material/{$externalIssue->id}/destroy");

        $response->assertStatus(302)
            ->assertRedirect(route('extern-issue-of-material.index'));

        $this->assertDatabaseMissing('external_issues', [
            'id' => $externalIssue->id
        ]);
    }

    public function testReturnExternalRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $externalIssue = ExternalIssue::create([
            'name' => 'Test External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'notes' => 'Test notes',
            'special_items_done' => false,
            'issued_by_id' => $user->id,
            'material_value' => 100.00,
            'external_name' => 'External Company'
        ]);

        $data = [
            'return_remarks' => 'Returned in good condition'
        ];

        $response = $this->post("/extern-issue-of-material/{$externalIssue->id}/return", $data);

        $response->assertStatus(302)
            ->assertRedirect(route('extern-issue-of-material.index'));
    }

    public function testSetSpecialItemsDoneRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $externalIssue = ExternalIssue::create([
            'name' => 'Test External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'notes' => 'Test notes',
            'special_items_done' => false,
            'issued_by_id' => $user->id,
            'material_value' => 100.00,
            'external_name' => 'External Company'
        ]);

        $response = $this->post("/extern-issue-of-material/{$externalIssue->id}/set-special-items-done");

        $response->assertStatus(302)
            ->assertRedirect(route('extern-issue-of-material.index'));

        $this->assertDatabaseHas('external_issues', [
            'id' => $externalIssue->id,
            'special_items_done' => true
        ]);
    }

    public function testFileDeleteReturnsJsonResponse(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $externalIssue = ExternalIssue::create([
            'name' => 'Test External Issue',
            'issue_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'notes' => 'Test notes',
            'special_items_done' => false,
            'issued_by_id' => $user->id,
            'material_value' => 100.00,
            'external_name' => 'External Company'
        ]);

        $file = ExternalIssueFile::create([
            'external_issue_id' => $externalIssue->id,
            'name' => 'test-file.pdf',
            'basename' => 'test-file',
            'path' => 'test/path/test-file.pdf',
            'file_path' => 'test/path/test-file.pdf',
            'original_name' => 'test-file.pdf'
        ]);

        $response = $this->deleteJson("/extern-issue-of-material/file/{$file->id}/delete");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'File deleted successfully'
            ]);

        $this->assertDatabaseMissing('external_issue_files', [
            'id' => $file->id
        ]);
    }
}
