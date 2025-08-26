<?php

/**
 * Test script to verify the checklist visibility fix
 *
 * This script tests that checklists created in OwnTasksManagement
 * and assigned to a project are visible in the ChecklistComponent
 */

require_once 'vendor/autoload.php';

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Project\Http\Resources\ProjectShowResource;
use Illuminate\Support\Facades\Auth;

echo "Testing Checklist Visibility Fix\n";
echo "================================\n\n";

// Test scenario: Create a checklist like OwnTasksManagement would
// (with user_id set and private = true, assigned to a project)

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

    // Simulate authentication
    Auth::loginUsingId($user->id);

    // Create a checklist like OwnTasksManagement would
    $checklist = new Checklist([
        'name' => 'Test Checklist from OwnTasksManagement',
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

    // Test the ProjectShowResource (used by ChecklistComponent)
    $projectResource = new ProjectShowResource($project);
    $projectData = $projectResource->toArray(null);

    echo "Testing ProjectShowResource filtering...\n";

    // Check if checklist appears in private_checklists
    $privateChecklists = collect($projectData['private_checklists']);
    $foundInPrivate = $privateChecklists->contains('id', $checklist->id);

    echo "- Found in private_checklists: " . ($foundInPrivate ? 'YES' : 'NO') . "\n";

    // Check if checklist appears in public_checklists (should not, since it's private)
    $publicChecklists = collect($projectData['public_checklists']);
    $foundInPublic = $publicChecklists->contains('id', $checklist->id);

    echo "- Found in public_checklists: " . ($foundInPublic ? 'YES (unexpected!)' : 'NO (correct)') . "\n\n";

    if ($foundInPrivate && !$foundInPublic) {
        echo "✅ SUCCESS: Checklist is correctly visible in private_checklists only!\n";
        echo "The fix appears to be working correctly.\n";
    } else {
        echo "❌ FAILURE: Checklist visibility is not working as expected.\n";
        if (!$foundInPrivate) {
            echo "- Private checklist should be visible to its creator\n";
        }
        if ($foundInPublic) {
            echo "- Private checklist should not appear in public checklists\n";
        }
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
