<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectIndexTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_project_index(): void
    {
        $this->get(route('projects'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_project_index(): void
    {
        $admin = $this->actingAsAdmin();
        // entities_per_page can be null by default; controller requires int via request param
        $admin->update(['entities_per_page' => 25]);

        $response = $this->get(route('projects'));

        $response->assertOk();
    }

    #[Test]
    public function authenticated_user_can_view_project_index(): void
    {
        $user = User::factory()->create(['entities_per_page' => 25]);
        $this->actingAs($user);

        $response = $this->get(route('projects'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_view_create_project_page(): void
    {
        $this->get(route('projects.create'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_create_project_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('projects.create'))->assertOk();
    }

    #[Test]
    public function guest_cannot_search_projects(): void
    {
        $this->get(route('projects.search', ['query' => 'foo']))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_search_projects(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('projects.search', ['query' => 'foo']));

        $response->assertOk();
    }

    #[Test]
    public function projects_can_be_searched_by_name(): void
    {
        $this->actingAsAdmin();

        $project = Project::factory()->create(['name' => 'Sommernachtstraum']);

        $response = $this->get(route('projects.search', ['query' => 'Sommernacht']));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $project->id, 'name' => 'Sommernachtstraum']);
    }

    #[Test]
    public function projects_can_be_searched_by_artist_name(): void
    {
        $this->actingAsAdmin();

        $project = Project::factory()->create([
            'name' => 'Gastspiel Herbst',
            'artists' => 'Maria Callas',
        ]);
        Project::factory()->create(['name' => 'Anderes Projekt', 'artists' => '']);

        $response = $this->get(route('projects.search', ['query' => 'Callas']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $project->id,
            'name' => 'Gastspiel Herbst',
            'artists' => 'Maria Callas',
        ]);
    }

    #[Test]
    public function project_search_returns_null_artists_when_none_set(): void
    {
        $this->actingAsAdmin();

        Project::factory()->create(['name' => 'Projekt ohne Besetzung', 'artists' => '']);

        $response = $this->get(route('projects.search', ['query' => 'ohne Besetzung']));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Projekt ohne Besetzung', 'artists' => null]);
    }

    #[Test]
    public function admin_can_search_departments_and_users(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('users_departments.search', ['query' => 'foo']));

        $response->assertOk();
        $response->assertJsonStructure(['departments', 'users']);
    }

    #[Test]
    public function guest_cannot_get_basic_project_info(): void
    {
        $project = Project::factory()->create();

        $this->getJson(route('projects.show.basic', $project))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_get_basic_project_info(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Basic Project']);

        $response = $this->getJson(route('projects.show.basic', $project));

        $response->assertOk();
        $response->assertJson(['id' => $project->id, 'name' => 'Basic Project']);
    }

    #[Test]
    public function admin_can_view_project_user_search(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('project.user.search', [
            'query' => 'xx',
            'projectId' => $project->id,
        ]));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function admin_can_save_project_management_filter(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('projects.filter'), [
            'project_state_ids' => [],
            'project_filters' => [],
        ]);

        // saveProjectManagementFilter returns void -> 200 OK
        $this->assertContains($response->getStatusCode(), [200, 201, 302]);
    }

    #[Test]
    public function pinned_project_matching_the_search_is_shown_in_pinned_section(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->update(['entities_per_page' => 25]);

        $pinned = Project::factory()->create([
            'name' => 'Gepinnte Traumproduktion',
            'pinned_by_users' => [$admin->id],
        ]);
        Project::factory()->create(['name' => 'Gepinnt ohne Treffer', 'pinned_by_users' => [$admin->id]]);
        $unpinnedHit = Project::factory()->create(['name' => 'Ungepinnte Traumproduktion']);

        $this->get(route('projects', ['query' => 'Traumproduktion']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pinnedProjects', fn ($pins) => collect($pins)->pluck('id')->all() === [$pinned->id])
                ->where('projects.data', fn ($projects) => collect($projects)->pluck('id')->all() === [$unpinnedHit->id]));
    }

    #[Test]
    public function pinned_search_hit_is_not_hidden_by_overview_filters(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->update(['entities_per_page' => 25]);

        // "Nur meine Projekte" würde das gepinnte Projekt (Admin nicht im Team) aus der Liste filtern
        $this->post(route('projects.filter'), [
            'project_state_ids' => [],
            'project_filters' => ['showOnlyMyProjects' => true],
        ]);

        $pinned = Project::factory()->create([
            'name' => 'Gepinnte Traumproduktion',
            'pinned_by_users' => [$admin->id],
        ]);

        $this->get(route('projects', ['query' => 'Traumproduktion']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pinnedProjects.0.id', $pinned->id));
    }

    #[Test]
    public function pinned_project_is_only_in_pinned_section_without_search(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->update(['entities_per_page' => 25]);

        $pinned = Project::factory()->create(['pinned_by_users' => [$admin->id]]);

        $this->get(route('projects'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pinnedProjects.0.id', $pinned->id)
                ->where('projects.data', fn ($projects) => !collect($projects)->pluck('id')->contains($pinned->id)));
    }
}
