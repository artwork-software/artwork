<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Serverseitige Autorisierung der BI-Routen (Phase 1 des BI-Ausbaus):
 * Lesen erfordert Projekt-Sichtrecht (ProjectPolicy::view), Schreiben das
 * Projekt-Schreibrecht (ProjectPolicy::update), Settings 'change tool settings'.
 */
final class BiRouteAuthorizationTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_read_bi_project_data(): void
    {
        $project = Project::factory()->create();

        $this->getJson(route('projects.bi.show', $project))->assertUnauthorized();
    }

    #[Test]
    public function user_without_project_view_cannot_read_bi_project_data(): void
    {
        $project = Project::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->getJson(route('projects.bi.show', $project))->assertForbidden();
        $this->getJson(route('projects.bi.snapshots.index', $project))->assertForbidden();
        $this->getJson(route('projects.bi.time-efforts.index', $project))->assertForbidden();
    }

    #[Test]
    public function user_with_project_view_can_read_but_not_write_bi_project_data(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->getJson(route('projects.bi.show', $project))->assertOk();

        $this->putJson(route('projects.bi.update-data', $project), ['visitors_total' => 10])
            ->assertForbidden();
        $this->postJson(route('projects.bi.snapshots.store', $project), [
            'name' => 'Test',
            'snapshot_date' => '2026-07-17',
        ])->assertForbidden();
        $this->postJson(route('projects.bi.time-efforts.store', $project), [
            'label' => 'Test',
            'effort_bucket' => '0-10',
        ])->assertForbidden();
    }

    #[Test]
    public function user_with_write_projects_can_update_bi_project_data(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->putJson(route('projects.bi.update-data', $project), ['visitors_total' => 10])
            ->assertOk();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'visitors_total' => 10,
        ]);
    }

    #[Test]
    public function bi_settings_require_change_tool_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('bi.settings.index'))->assertForbidden();
        $this->postJson(route('bi.settings.store'), ['name' => 'Feld'])->assertForbidden();
    }

    #[Test]
    public function bi_settings_are_accessible_with_change_tool_settings_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->get(route('bi.settings.index'))->assertOk();
    }
}
