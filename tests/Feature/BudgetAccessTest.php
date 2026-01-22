<?php

namespace Tests\Feature;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BudgetAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_team_member_with_budget_access_receives_correct_payload(): void
    {
        // 1. Setup: Create a project and a user
        $project = Project::factory()->create();
        $user = User::factory()->create();

        // Ensure project has a budget table
        $project->table()->create(['name' => 'Test Table']);

        // 2. Assign user to project with access_budget = true
        $project->users()->attach($user->id, [
            'access_budget' => true,
            'is_manager' => false,
            'can_write' => true,
        ]);

        // 3. Act: Request the budget tab data as this user
        $this->actingAs($user);
        $response = $this->getJson(route('projects.tabs.budget', ['project' => $project->id]));

        // 4. Assert: Check if payload contains the user in access_budget
        $response->assertStatus(200);
        $response->assertJsonPath('access_budget.0.id', $user->id);
        $response->assertJsonFragment(['pivot_access_budget' => true]);

        // Also check managerUsers is empty for this user
        $response->assertJsonCount(0, 'managerUsers');
    }

    public function test_team_member_as_manager_receives_correct_payload(): void
    {
        // 1. Setup: Create a project and a user
        $project = Project::factory()->create();
        $user = User::factory()->create();

        // Ensure project has a budget table
        $project->table()->create(['name' => 'Test Table']);

        // 2. Assign user to project with is_manager = true
        $project->users()->attach($user->id, [
            'access_budget' => false,
            'is_manager' => true,
            'can_write' => true,
        ]);

        // 3. Act: Request the budget tab data
        $this->actingAs($user);
        $response = $this->getJson(route('projects.tabs.budget', ['project' => $project->id]));

        // 4. Assert
        $response->assertStatus(200);
        $response->assertJsonPath('managerUsers.0.id', $user->id);
        $response->assertJsonFragment(['pivot_is_manager' => true]);
    }
}
