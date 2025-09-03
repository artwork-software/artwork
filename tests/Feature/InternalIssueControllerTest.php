<?php

namespace Tests\Feature;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class InternalIssueControllerTest extends TestCase
{
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
}
