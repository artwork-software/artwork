<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategoryValue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BiAudienceCategoryTest extends FeatureTestCase
{
    private function makeCategory(array $attributes = []): BiAudienceCategory
    {
        return BiAudienceCategory::create(array_merge([
            'name' => 'Vollzahler*innen',
            'pricing_type' => 'full',
            'position' => 0,
            'is_active' => true,
        ], $attributes));
    }

    #[Test]
    public function category_management_requires_tool_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('bi.audience-categories.store'), [
            'name' => 'Test',
            'pricing_type' => 'free',
        ])->assertForbidden();
    }

    #[Test]
    public function categories_can_be_created_and_updated(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $response = $this->postJson(route('bi.audience-categories.store'), [
            'name' => 'Presse',
            'pricing_type' => 'free',
        ])->assertCreated();

        $categoryId = $response->json('id');

        $this->patchJson(route('bi.audience-categories.update', $categoryId), [
            'name' => 'Presse & Gäste',
            'is_active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('bi_audience_categories', [
            'id' => $categoryId,
            'name' => 'Presse & Gäste',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function category_with_values_cannot_be_deleted(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $category = $this->makeCategory();
        BiAudienceCategoryValue::create([
            'project_id' => Project::factory()->create()->id,
            'bi_audience_category_id' => $category->id,
            'event_id' => null,
            'scope' => 'actual',
            'quantity' => 10,
        ]);

        $this->deleteJson(route('bi.audience-categories.destroy', $category->id))
            ->assertUnprocessable();

        $this->assertDatabaseHas('bi_audience_categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    #[Test]
    public function category_values_can_be_upserted_and_cleared(): void
    {
        $project = Project::factory()->create();
        $category = $this->makeCategory();
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->putJson(route('projects.bi.upsert-category-values', $project), [
            'values' => [
                ['bi_audience_category_id' => $category->id, 'event_id' => null, 'quantity' => 120],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('bi_audience_category_values', [
            'project_id' => $project->id,
            'bi_audience_category_id' => $category->id,
            'quantity' => 120,
        ]);

        // quantity null löscht den Eintrag statt eine 0 zu speichern
        $this->putJson(route('projects.bi.upsert-category-values', $project), [
            'values' => [
                ['bi_audience_category_id' => $category->id, 'event_id' => null, 'quantity' => null],
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('bi_audience_category_values', [
            'project_id' => $project->id,
            'bi_audience_category_id' => $category->id,
        ]);
    }

    #[Test]
    public function category_values_reject_events_of_other_projects(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $foreignEvent = \Artwork\Modules\Event\Models\Event::factory()->create([
            'project_id' => $otherProject->id,
        ]);
        $category = $this->makeCategory();
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);

        $this->putJson(route('projects.bi.upsert-category-values', $project), [
            'values' => [
                ['bi_audience_category_id' => $category->id, 'event_id' => $foreignEvent->id, 'quantity' => 5],
            ],
        ])->assertNotFound();
    }

    #[Test]
    public function category_values_require_project_write_permission(): void
    {
        $project = Project::factory()->create();
        $category = $this->makeCategory();
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->putJson(route('projects.bi.upsert-category-values', $project), [
            'values' => [
                ['bi_audience_category_id' => $category->id, 'event_id' => null, 'quantity' => 1],
            ],
        ])->assertForbidden();
    }

    #[Test]
    public function bi_show_payload_contains_categories_and_values(): void
    {
        $project = Project::factory()->create();
        $category = $this->makeCategory();
        BiAudienceCategoryValue::create([
            'project_id' => $project->id,
            'bi_audience_category_id' => $category->id,
            'event_id' => null,
            'scope' => 'actual',
            'quantity' => 55,
        ]);
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);

        $response = $this->getJson(route('projects.bi.show', $project))->assertOk();

        $this->assertSame('Vollzahler*innen', $response->json('audience_categories.0.name'));
        $this->assertSame(55, $response->json('audience_category_values.0.quantity'));
    }
}
