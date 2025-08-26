<?php

/**
 * Test script to verify the ChecklistService fix
 *
 * This script tests that checklists created in OwnTasksManagement
 * and assigned to a project are visible via ChecklistService methods
 */

require_once 'vendor/autoload.php';

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Project\Models\ComponentInTab;
use Illuminate\Support\Facades\Auth;

echo "Testing ChecklistService Fix\n";
echo "============================\n\n";

try {
    // Find a test user and project
    $user = User::first();
    $project = Project::first();

    if (!$user || !$project) {
        echo "ERROR: No user or project found in database. Please run migrations and seeders.\n";
        exit(1);
    }

    echo "Test User: {$user->first_name} {$user->last_name} (ID: {$user->id})\n";
    echo "Test Project: {$project->name} (ID: {$project->id})\n\n";

    // Add user to project team (this simulates the real scenario)
    if (!$project->users->contains($user->id)) {
        $project->users()->attach($user->id);
        echo "Added user to project team.\n";
    } else {
        echo "User already in project team.\n";
    }

    // Simulate authentication
    Auth::loginUsingId($user->id);

    // Create a checklist like OwnTasksManagement would
    $checklist = new Checklist([
        'name' => 'Test Checklist from OwnTasksManagement (ChecklistService)',
        'user_id' => $user->id,        // Set by OwnTasksManagement (creator)
        'private' => true,             // Private checklist
        'project_id' => $project->id,  // Assigned to project
        'tab_id' => 1                  // Default tab
    ]);

    $checklist->save();
    echo "Created test checklist: {$checklist->name} (ID: {$checklist->id})\n";
    echo "- Created by user: {$user->id}\n";
    echo "- Private: " . ($checklist->private ? 'true' : 'false') . "\n";
    echo "- Assigned to project: {$project->id}\n\n";

    // Test ChecklistService methods
    $checklistService = app(ChecklistService::class);
    $headerObject = new stdClass();
    $headerObject->project = $project;

    // Create a mock ComponentInTab for testing
    $componentInTab = new class {
        public $scope = [1]; // Tab ID 1
    };

    echo "Testing ChecklistService->getProjectChecklists()...\n";
    $result = $checklistService->getProjectChecklists($project, $headerObject, $componentInTab);

    // Check if checklist appears in private_checklists
    $privateChecklists = collect($result->project->private_checklists);
    $foundInPrivate = $privateChecklists->contains('id', $checklist->id);

    echo "- Found in private_checklists: " . ($foundInPrivate ? 'YES' : 'NO') . "\n";

    if ($foundInPrivate) {
        echo "✅ SUCCESS: Checklist is now correctly visible in private_checklists!\n";
        echo "The ChecklistService fix is working correctly.\n";
    } else {
        echo "❌ FAILURE: Checklist is still not visible in private_checklists.\n";

        // Debug info
        echo "\nDebug Information:\n";
        echo "- User is creator: " . ($checklist->user_id === $user->id ? 'true' : 'false') . "\n";
        echo "- User in project team: " . ($project->users->contains($user->id) ? 'true' : 'false') . "\n";
        echo "- Checklist project_id: {$checklist->project_id}\n";
        echo "- Project id: {$project->id}\n";
        echo "- Checklist tab_id: {$checklist->tab_id}\n";
        echo "- Component scope: " . json_encode($componentInTab->scope) . "\n";
    }

    // Clean up
    $checklist->delete();
    echo "\nCleaned up test checklist.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nTest completed.\n";
