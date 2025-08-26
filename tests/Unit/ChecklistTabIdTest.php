<?php

namespace Tests\Unit;

use Tests\TestCase;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Project\Models\ComponentInTab;
use Illuminate\Support\Facades\Auth;

class ChecklistTabIdTest extends TestCase
{
    public function testChecklistWithCorrectTabIdIsVisibleInProjectChecklists(): void
    {
        // Create test data
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Add user to project team
        $project->users()->attach($user->id);

        // Authenticate the user
        Auth::loginUsingId($user->id);

        // Create a checklist with the correct tab_id (matching the component scope)
        $checklist = Checklist::factory()->create([
            'name' => 'Test Checklist with Correct Tab ID',
            'user_id' => $user->id,
            'private' => true,
            'project_id' => $project->id,
            'tab_id' => 1 // This matches the component scope
        ]);

        // Create a ComponentInTab with scope containing tab_id 1
        $componentInTab = new ComponentInTab();
        $componentInTab->scope = [1, 2, 3]; // Tab IDs that should be included

        // Test the ChecklistService
        $checklistService = app(ChecklistService::class);
        $headerObject = new \stdClass();
        $headerObject->project = $project;

        $result = $checklistService->getProjectChecklists($project, $headerObject, $componentInTab);

        // The checklist should appear in private_checklists because:
        // - It has tab_id = 1 which is in the component scope [1, 2, 3]
        // - The user is the creator AND in the project team
        $privateChecklistIds = collect($result->project->private_checklists)->pluck('id')->toArray();
        $this->assertContains($checklist->id, $privateChecklistIds,
            'Checklist with correct tab_id should be visible in private_checklists');
    }

    public function testChecklistWithNullTabIdIsNotVisibleInProjectChecklists(): void
    {
        // Create test data
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Add user to project team
        $project->users()->attach($user->id);

        // Authenticate the user
        Auth::loginUsingId($user->id);

        // Create a checklist with null tab_id (like the old OwnTasksManagement behavior)
        $checklist = Checklist::factory()->create([
            'name' => 'Test Checklist with Null Tab ID',
            'user_id' => $user->id,
            'private' => true,
            'project_id' => $project->id,
            'tab_id' => null // This should NOT match any component scope
        ]);

        // Create a ComponentInTab with scope containing specific tab IDs
        $componentInTab = new ComponentInTab();
        $componentInTab->scope = [1, 2, 3]; // Tab IDs that should be included

        // Test the ChecklistService
        $checklistService = app(ChecklistService::class);
        $headerObject = new \stdClass();
        $headerObject->project = $project;

        $result = $checklistService->getProjectChecklists($project, $headerObject, $componentInTab);

        // The checklist should NOT appear in private_checklists because:
        // - It has tab_id = null which is NOT in the component scope [1, 2, 3]
        // - Even though the user is the creator and in the project team
        $privateChecklistIds = collect($result->project->private_checklists)->pluck('id')->toArray();
        $this->assertNotContains($checklist->id, $privateChecklistIds,
            'Checklist with null tab_id should not be visible in private_checklists');
    }

    public function testChecklistWithWrongTabIdIsNotVisibleInProjectChecklists(): void
    {
        // Create test data
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Add user to project team
        $project->users()->attach($user->id);

        // Authenticate the user
        Auth::loginUsingId($user->id);

        // Create a checklist with wrong tab_id (not in component scope)
        $checklist = Checklist::factory()->create([
            'name' => 'Test Checklist with Wrong Tab ID',
            'user_id' => $user->id,
            'private' => true,
            'project_id' => $project->id,
            'tab_id' => 99 // This is NOT in the component scope
        ]);

        // Create a ComponentInTab with scope that doesn't include tab_id 99
        $componentInTab = new ComponentInTab();
        $componentInTab->scope = [1, 2, 3]; // Tab IDs that should be included (99 is not here)

        // Test the ChecklistService
        $checklistService = app(ChecklistService::class);
        $headerObject = new \stdClass();
        $headerObject->project = $project;

        $result = $checklistService->getProjectChecklists($project, $headerObject, $componentInTab);

        // The checklist should NOT appear in private_checklists because:
        // - It has tab_id = 99 which is NOT in the component scope [1, 2, 3]
        $privateChecklistIds = collect($result->project->private_checklists)->pluck('id')->toArray();
        $this->assertNotContains($checklist->id, $privateChecklistIds,
            'Checklist with wrong tab_id should not be visible in private_checklists');
    }
}
