<?php

namespace Tests\Unit;

use Tests\TestCase;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Project\Http\Resources\ProjectShowResource;
use Illuminate\Support\Facades\Auth;

class ChecklistVisibilityTest extends TestCase
{
    public function testChecklistCreatedInOwnTasksManagementAppearsInProjectShowResource(): void
    {
        // Create test user and project
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Authenticate the user
        Auth::loginUsingId($user->id);

        // Create a checklist like OwnTasksManagement would:
        // - user_id is set (creator)
        // - private = true
        // - project_id is set (assigned to project)
        $checklist = Checklist::factory()->create([
            'name' => 'Test Checklist from OwnTasksManagement',
            'user_id' => $user->id,
            'private' => true,
            'project_id' => $project->id,
            'tab_id' => 1
        ]);

        // Test the ProjectShowResource (used by ChecklistComponent)
        $projectResource = new ProjectShowResource($project);
        $projectData = $projectResource->toArray(null);

        // The checklist should appear in private_checklists because:
        // - It's private AND the user is the creator
        $privateChecklistIds = collect($projectData['private_checklists'])->pluck('id')->toArray();
        $this->assertContains($checklist->id, $privateChecklistIds,
            'Private checklist should be visible to its creator in private_checklists');

        // The checklist should NOT appear in public_checklists because it's private
        $publicChecklistIds = collect($projectData['public_checklists'])->pluck('id')->toArray();
        $this->assertNotContains($checklist->id, $publicChecklistIds,
            'Private checklist should not appear in public_checklists');
    }

    public function testChecklistCreatedByOtherUserIsNotVisibleUnlessAssigned(): void
    {
        // Create two users and a project
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create();

        // Creator creates a private checklist
        $checklist = Checklist::factory()->create([
            'name' => 'Private Checklist by Creator',
            'user_id' => $creator->id,
            'private' => true,
            'project_id' => $project->id,
            'tab_id' => 1
        ]);

        // Authenticate as the other user
        Auth::loginUsingId($otherUser->id);

        // Test the ProjectShowResource
        $projectResource = new ProjectShowResource($project);
        $projectData = $projectResource->toArray(null);

        // The checklist should NOT appear in private_checklists because:
        // - Other user is not the creator, not assigned to checklist, and has no tasks
        $privateChecklistIds = collect($projectData['private_checklists'])->pluck('id')->toArray();
        $this->assertNotContains($checklist->id, $privateChecklistIds,
            'Private checklist should not be visible to users who are not creator or assigned');
    }

    public function testPublicChecklistIsVisibleToProjectTeamMembers(): void
    {
        // Create user and project
        $user = User::factory()->create();
        $creator = User::factory()->create();
        $project = Project::factory()->create();

        // Add user to project team
        $project->users()->attach($user->id);

        // Creator creates a public checklist
        $checklist = Checklist::factory()->create([
            'name' => 'Public Checklist',
            'user_id' => $creator->id,
            'private' => false,
            'project_id' => $project->id,
            'tab_id' => 1
        ]);

        // Authenticate as the team member
        Auth::loginUsingId($user->id);

        // Test the ProjectShowResource
        $projectResource = new ProjectShowResource($project);
        $projectData = $projectResource->toArray(null);

        // The checklist should appear in public_checklists because:
        // - It's public AND the user is in the project team
        $publicChecklistIds = collect($projectData['public_checklists'])->pluck('id')->toArray();
        $this->assertContains($checklist->id, $publicChecklistIds,
            'Public checklist should be visible to project team members');
    }

    public function testChecklistWithAssignedUserIsVisible(): void
    {
        // Create user, creator and project
        $user = User::factory()->create();
        $creator = User::factory()->create();
        $project = Project::factory()->create();

        // Creator creates a private checklist and assigns the user to it
        $checklist = Checklist::factory()->create([
            'name' => 'Assigned Private Checklist',
            'user_id' => $creator->id,
            'private' => true,
            'project_id' => $project->id,
            'tab_id' => 1
        ]);

        // Assign user to the checklist
        $checklist->users()->attach($user->id);

        // Authenticate as the assigned user
        Auth::loginUsingId($user->id);

        // Test the ProjectShowResource
        $projectResource = new ProjectShowResource($project);
        $projectData = $projectResource->toArray(null);

        // The checklist should appear in private_checklists because:
        // - It's private AND the user is assigned to the checklist
        $privateChecklistIds = collect($projectData['private_checklists'])->pluck('id')->toArray();
        $this->assertContains($checklist->id, $privateChecklistIds,
            'Private checklist should be visible to assigned users');
    }
}
