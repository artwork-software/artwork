<?php

namespace Tests\Feature;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssueFile;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class InternalIssueControllerTest extends TestCase
{
    public function testStoreRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();
        $room = Room::factory()->create();

        $data = [
            'name' => 'Test Internal Issue',
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_date' => '2024-01-01',
            'start_time' => '10:00',
            'end_date' => '2024-01-01',
            'end_time' => '12:00',
            'notes' => 'Test notes',
            'special_items_done' => false
        ];

        $response = $this->post("/issue-of-material/store", $data);

        $response->assertStatus(302)
            ->assertRedirect(route('issue-of-material.index'));
    }

    public function testUpdateRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();
        $room = Room::factory()->create();

        $internalIssue = InternalIssue::create([
            'name' => 'Test Internal Issue',
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_date' => '2024-01-01',
            'start_time' => '10:00:00',
            'end_date' => '2024-01-01',
            'end_time' => '12:00:00',
            'notes' => 'Test notes',
            'special_items_done' => false
        ]);

        $data = [
            'id' => $internalIssue->id,
            'name' => 'Updated Internal Issue',
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_date' => '2024-01-01',
            'start_time' => '10:00',
            'end_date' => '2024-01-01',
            'end_time' => '14:00',
            'notes' => 'Updated notes',
            'special_items_done' => false
        ];

        $response = $this->patch("/issue-of-material/{$internalIssue->id}/update", $data);

        $response->assertStatus(302)
            ->assertRedirect(route('issue-of-material.index'));
    }

    public function testDestroyRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();
        $room = Room::factory()->create();

        $internalIssue = InternalIssue::create([
            'name' => 'Test Internal Issue',
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_date' => '2024-01-01',
            'start_time' => '10:00:00',
            'end_date' => '2024-01-01',
            'end_time' => '12:00:00',
            'notes' => 'Test notes',
            'special_items_done' => false
        ]);

        $response = $this->delete("/issue-of-material/{$internalIssue->id}/destroy");

        $response->assertStatus(302)
            ->assertRedirect(route('issue-of-material.index'));

        $this->assertDatabaseMissing('internal_issues', [
            'id' => $internalIssue->id
        ]);
    }

    public function testSetSpecialItemsDoneRedirectsToIndex(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();
        $room = Room::factory()->create();

        $internalIssue = InternalIssue::create([
            'name' => 'Test Internal Issue',
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_date' => '2024-01-01',
            'start_time' => '10:00:00',
            'end_date' => '2024-01-01',
            'end_time' => '12:00:00',
            'notes' => 'Test notes',
            'special_items_done' => false
        ]);

        $response = $this->post("/issue-of-material/{$internalIssue->id}/set-special-items-done");

        $response->assertStatus(302)
            ->assertRedirect(route('issue-of-material.index'));

        $this->assertDatabaseHas('internal_issues', [
            'id' => $internalIssue->id,
            'special_items_done' => true
        ]);
    }

    public function testFileDeleteReturnsJsonResponse(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();
        $room = Room::factory()->create();

        $internalIssue = InternalIssue::create([
            'name' => 'Test Internal Issue',
            'project_id' => $project->id,
            'room_id' => $room->id,
            'start_date' => '2024-01-01',
            'start_time' => '10:00:00',
            'end_date' => '2024-01-01',
            'end_time' => '12:00:00',
            'notes' => 'Test notes',
            'special_items_done' => false
        ]);

        $file = InternalIssueFile::create([
            'internal_issue_id' => $internalIssue->id,
            'name' => 'test-file.pdf',
            'basename' => 'test-file',
            'path' => 'test/path/test-file.pdf',
            'file_path' => 'test/path/test-file.pdf',
            'original_name' => 'test-file.pdf'
        ]);

        $response = $this->deleteJson("/issue-of-material/file/{$file->id}/delete");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'File deleted successfully'
            ]);

        $this->assertDatabaseMissing('internal_issue_files', [
            'id' => $file->id
        ]);
    }
}
