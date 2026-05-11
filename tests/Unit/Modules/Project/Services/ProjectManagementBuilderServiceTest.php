<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Project\Services\ProjectManagementBuilderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectManagementBuilderServiceTest extends TestCase
{
    private ProjectManagementBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectManagementBuilderService::class);
    }

    #[Test]
    public function get_project_management_builder_returns_collection(): void
    {
        ProjectManagementBuilder::factory()->count(2)->create();

        $result = $this->service->getProjectManagementBuilder();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    #[Test]
    public function update_order_changes_order_per_component(): void
    {
        $first = ProjectManagementBuilder::factory()->create(['order' => 1]);
        $second = ProjectManagementBuilder::factory()->create(['order' => 2]);

        $this->service->updateOrder(new SupportCollection([
            ['id' => $first->id, 'order' => 10],
            ['id' => $second->id, 'order' => 20],
        ]));

        $this->assertSame(10, $first->fresh()->order);
        $this->assertSame(20, $second->fresh()->order);
    }

    #[Test]
    public function reorder_components_resets_sequence_to_one_based(): void
    {
        $a = ProjectManagementBuilder::factory()->create(['order' => 5]);
        $b = ProjectManagementBuilder::factory()->create(['order' => 8]);
        $c = ProjectManagementBuilder::factory()->create(['order' => 12]);

        $this->service->reorderComponents();

        $ordered = ProjectManagementBuilder::query()
            ->whereIn('id', [$a->id, $b->id, $c->id])
            ->orderBy('order')
            ->pluck('order')
            ->toArray();

        $this->assertSame([1, 2, 3], array_values(array_slice($ordered, -3)));
    }
}
