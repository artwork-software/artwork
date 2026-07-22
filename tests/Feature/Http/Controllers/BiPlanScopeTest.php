<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Plan/Ist-Dimension (Phase 3 des BI-Ausbaus): Scope-Isolation der Datensätze,
 * Plan-Schnellstart und die Plan-Ist-Gegenüberstellung im show-Payload.
 */
final class BiPlanScopeTest extends FeatureTestCase
{
    #[Test]
    public function plan_and_actual_data_are_isolated_per_scope(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([
            PermissionEnum::WRITE_PROJECTS->value,
            PermissionEnum::PROJECT_VIEW->value,
        ]);

        $this->putJson(route('projects.bi.update-data', $project), [
            'visitors_total' => 800,
        ])->assertOk();

        $this->putJson(route('projects.bi.update-data', $project), [
            'visitors_total' => 1000,
            'scope' => 'plan',
        ])->assertOk();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'actual',
            'visitors_total' => 800,
        ]);
        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'plan',
            'visitors_total' => 1000,
        ]);

        $response = $this->getJson(route('projects.bi.show', $project))->assertOk();

        $this->assertSame(800, $response->json('bi_data.visitors_total'));
        $this->assertSame(1000, $response->json('plan_data.visitors_total'));
        $this->assertTrue($response->json('plan_comparison.has_plan'));
        $this->assertEquals(80, $response->json('plan_comparison.metrics.visitors.attainment'));
    }

    #[Test]
    public function show_reports_no_plan_when_only_actuals_exist(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([
            PermissionEnum::WRITE_PROJECTS->value,
            PermissionEnum::PROJECT_VIEW->value,
        ]);

        $this->putJson(route('projects.bi.update-data', $project), ['visitors_total' => 500])->assertOk();

        $response = $this->getJson(route('projects.bi.show', $project))->assertOk();

        $this->assertNull($response->json('plan_data'));
        $this->assertFalse($response->json('plan_comparison.has_plan'));
        $this->assertNull($response->json('plan_metrics_summary'));
    }

    #[Test]
    public function plan_can_be_initialized_with_actual_structure(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->putJson(route('projects.bi.update-data', $project), ['visitors_total' => 1])->assertOk();
        $this->putJson(route('projects.bi.switch-revenue-mode', $project), ['mode' => 'per_event'])->assertOk();

        $this->postJson(route('projects.bi.plan.initialize', $project), [
            'mode' => 'copy_actual_structure',
        ])->assertOk();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'plan',
            'revenue_mode' => 'per_event',
            'visitors_total' => null,
        ]);
    }

    #[Test]
    public function plan_can_copy_totals_from_another_project(): void
    {
        $source = Project::factory()->create();
        $target = Project::factory()->create();
        $this->actingAsUserWith([
            PermissionEnum::WRITE_PROJECTS->value,
            PermissionEnum::PROJECT_VIEW->value,
        ]);

        $this->putJson(route('projects.bi.update-data', $source), [
            'visitors_total' => 1234,
            'revenue_total' => 5678.5,
        ])->assertOk();

        $this->postJson(route('projects.bi.plan.initialize', $target), [
            'mode' => 'copy_project',
            'source_project_id' => $source->id,
        ])->assertOk();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $target->id,
            'scope' => 'plan',
            'visitors_total' => 1234,
            'revenue_total' => 5678.5,
        ]);
    }

    #[Test]
    public function plan_initialize_requires_write_permission(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->postJson(route('projects.bi.plan.initialize', $project), ['mode' => 'empty'])
            ->assertForbidden();
    }

    #[Test]
    public function snapshots_can_freeze_the_plan_scope(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->putJson(route('projects.bi.update-data', $project), [
            'visitors_total' => 1000,
            'scope' => 'plan',
        ])->assertOk();

        $this->postJson(route('projects.bi.snapshots.store', $project), [
            'name' => 'Plan eingefroren',
            'snapshot_date' => '2026-07-17',
            'scope' => 'plan',
        ])->assertCreated();

        $this->assertDatabaseHas('bi_snapshots', [
            'project_id' => $project->id,
            'scope' => 'plan',
            'name' => 'Plan eingefroren',
        ]);

        $snapshot = $project->biSnapshots()->first();
        $this->assertSame(1000, $snapshot->data['bi_data']['visitors_total']);
    }

    #[Test]
    public function revenue_source_is_stamped_and_cleared(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->putJson(route('projects.bi.update-data', $project), [
            'revenue_total' => 5000,
            'revenue_source' => 'sage',
        ])->assertOk();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'actual',
            'revenue_source' => 'sage',
        ]);

        // Manuelles Überschreiben löscht die Herkunft
        $this->putJson(route('projects.bi.update-data', $project), [
            'revenue_total' => 6000,
        ])->assertOk();

        $this->assertDatabaseHas('bi_project_data', [
            'project_id' => $project->id,
            'scope' => 'actual',
            'revenue_source' => null,
        ]);
    }
}
