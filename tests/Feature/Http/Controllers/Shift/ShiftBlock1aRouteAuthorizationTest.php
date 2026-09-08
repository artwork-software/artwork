<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 1a (Release-Härtung): Routen, die bisher nur hinter Auth lagen, verlangen
 * jetzt explizit das Planungs- bzw. Schichtplan-Leserecht; Externen-Massenzuordnung
 * und Konditionen sind policy-geschützt bzw. validiert.
 */
final class ShiftBlock1aRouteAuthorizationTest extends FeatureTestCase
{
    #[Test]
    public function day_service_attach_and_remove_require_planning_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $dayService = DayService::factory()->create();
        $target = User::factory()->create();

        $this->postJson(
            route('day-service.attach', ['dayService' => $dayService->id, 'dayServiceable' => $target->id]),
            ['modelType' => 'user', 'date' => '2026-06-08']
        )->assertForbidden();

        $this->patchJson(
            route('remove.day.service.from.user', ['dayServiceable' => $target->id]),
            ['modelType' => 'user', 'date' => '2026-06-08', 'dayService' => $dayService->id]
        )->assertForbidden();
    }

    #[Test]
    public function planner_can_attach_and_remove_day_services(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $dayService = DayService::factory()->create();
        $target = User::factory()->create();

        $this->postJson(
            route('day-service.attach', ['dayService' => $dayService->id, 'dayServiceable' => $target->id]),
            ['modelType' => 'user', 'date' => '2026-06-08']
        )->assertOk();

        $this->assertDatabaseHas('day_serviceables', [
            'day_service_id' => $dayService->id,
            'day_serviceable_id' => $target->id,
        ]);

        $this->patchJson(
            route('remove.day.service.from.user', ['dayServiceable' => $target->id]),
            ['modelType' => 'user', 'date' => '2026-06-08', 'dayService' => $dayService->id]
        )->assertOk();

        $this->assertDatabaseMissing('day_serviceables', [
            'day_service_id' => $dayService->id,
            'day_serviceable_id' => $target->id,
        ]);
    }

    #[Test]
    public function shift_plan_exports_require_view_shift_plan_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $project = Project::factory()->create();

        $this->postJson(route('shift.plan.export.pdf'), [])->assertForbidden();
        $this->get(route('projects.exports.shift-plan', ['project' => $project->id, 'privacyMode' => 0]))
            ->assertForbidden();
        $this->get(route('projects.exports.shifts-personal-plan', ['project' => $project->id]))
            ->assertForbidden();
    }

    #[Test]
    public function shift_plan_viewer_passes_authorization_on_exports(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $project = Project::factory()->create();

        $this->assertNotSame(
            403,
            $this->post(route('shift.plan.export.pdf'), [])->getStatusCode()
        );
        $this->assertNotSame(
            403,
            $this->get(route('projects.exports.shift-plan', ['project' => $project->id, 'privacyMode' => 0]))
                ->getStatusCode()
        );
        $this->assertNotSame(
            403,
            $this->get(route('projects.exports.shifts-personal-plan', ['project' => $project->id]))
                ->getStatusCode()
        );
    }

    #[Test]
    public function check_collisions_requires_planning_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $this->postJson(route('shift.check-collisions'), [])->assertForbidden();
    }

    #[Test]
    public function planner_reaches_check_collisions(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        // Ohne Pflichtparameter antwortet der Controller selbst mit 400 — die
        // Middleware hat also durchgelassen.
        $this->postJson(route('shift.check-collisions'), [])->assertStatus(400);
    }

    #[Test]
    public function timeline_routes_require_planning_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $event = Event::factory()->create();

        $this->postJson(route('edit.timeline.event', $event), ['dataset' => []])->assertForbidden();
        $this->postJson(route('create.timeline.event', $event), ['dataset' => []])->assertForbidden();
    }

    #[Test]
    public function planner_can_use_timeline_routes(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $event = Event::factory()->create();

        $this->postJson(route('edit.timeline.event', $event), ['dataset' => []])->assertOk();
        $this->postJson(route('create.timeline.event', $event), ['dataset' => []])->assertOk();
    }

    #[Test]
    public function assign_crafts_bulk_requires_external_management_permission(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $this->patchJson(route('freelancer.assign.crafts.bulk', $freelancer), ['craftIds' => []])
            ->assertForbidden();
        $this->patchJson(route('service_provider.assign.crafts.bulk', $serviceProvider), ['craftIds' => []])
            ->assertForbidden();
    }

    #[Test]
    public function external_manager_can_assign_crafts_bulk(): void
    {
        $this->actingAsUserWith(PermissionEnum::EXTERNAL_MANAGER->value);
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $this->patch(route('freelancer.assign.crafts.bulk', $freelancer), ['craftIds' => []])
            ->assertRedirect();
        $this->patch(route('service_provider.assign.crafts.bulk', $serviceProvider), ['craftIds' => []])
            ->assertRedirect();
    }

    #[Test]
    public function update_terms_rejects_negative_salary(): void
    {
        $this->actingAsUserWith(PermissionEnum::EXTERNAL_MANAGER->value);
        $freelancer = Freelancer::factory()->create();
        $serviceProvider = ServiceProvider::factory()->create();

        $this->patchJson(route('freelancer.update.terms', $freelancer), ['salary_per_hour' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['salary_per_hour']);
        $this->patchJson(route('service_provider.update.terms', $serviceProvider), ['salary_per_hour' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['salary_per_hour']);
    }

    #[Test]
    public function update_terms_stores_valid_values(): void
    {
        $this->actingAsUserWith(PermissionEnum::EXTERNAL_MANAGER->value);
        $freelancer = Freelancer::factory()->create();

        $this->patchJson(route('freelancer.update.terms', $freelancer), [
            'salary_per_hour' => 42,
            'salary_description' => 'Tagessatz nach Absprache',
        ])->assertOk();

        $this->assertDatabaseHas('freelancers', [
            'id' => $freelancer->id,
            'salary_per_hour' => 42,
            'salary_description' => 'Tagessatz nach Absprache',
        ]);
    }
}
