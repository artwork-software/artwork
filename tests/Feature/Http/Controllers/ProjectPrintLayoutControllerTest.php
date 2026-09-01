<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectPrintLayoutControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_project_print_layout_index(): void
    {
        $this->get(route('project-print-layout.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_project_print_layout_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('project-print-layout.index'));

        $response->assertOk();
    }

    #[Test]
    public function component_can_be_moved_to_an_empty_cell(): void
    {
        $admin = $this->actingAsAdmin();
        $layout = $this->createLayout($admin->id);
        $component = $this->createPlacedComponent($layout, row: 1, position: 1);

        $this->patch(
            route('project-print-layout.components.move', ['printLayoutComponent' => $component->id]),
            ['type' => 'body', 'row' => 2, 'col' => 1]
        );

        $this->assertDatabaseHas('print_layout_components', [
            'id' => $component->id,
            'type' => 'body',
            'row' => 2,
            'position' => 1,
        ]);
    }

    #[Test]
    public function moving_onto_an_occupied_cell_swaps_both_components(): void
    {
        $admin = $this->actingAsAdmin();
        $layout = $this->createLayout($admin->id);
        $first = $this->createPlacedComponent($layout, row: 1, position: 1);
        $second = $this->createPlacedComponent($layout, row: 2, position: 1);

        $this->patch(
            route('project-print-layout.components.move', ['printLayoutComponent' => $first->id]),
            ['type' => 'body', 'row' => 2, 'col' => 1]
        );

        $this->assertDatabaseHas('print_layout_components', [
            'id' => $first->id,
            'row' => 2,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('print_layout_components', [
            'id' => $second->id,
            'row' => 1,
            'position' => 1,
        ]);
    }

    #[Test]
    public function guest_cannot_move_a_component(): void
    {
        $admin = $this->adminUser();
        $layout = $this->createLayout($admin->id);
        $component = $this->createPlacedComponent($layout, row: 1, position: 1);

        $this->patch(
            route('project-print-layout.components.move', ['printLayoutComponent' => $component->id]),
            ['type' => 'body', 'row' => 2, 'col' => 1]
        )->assertRedirect(route('login'));

        $this->assertDatabaseHas('print_layout_components', [
            'id' => $component->id,
            'row' => 1,
            'position' => 1,
        ]);
    }

    private function createLayout(int $userId): ProjectPrintLayout
    {
        return ProjectPrintLayout::create([
            'name' => 'Test Layout',
            'description' => 'Test',
            'columns_header' => 1,
            'columns_body' => 1,
            'columns_footer' => 1,
            'order' => 1,
            'user_id' => $userId,
            'notes' => ['header' => [], 'footer' => []],
        ]);
    }

    private function createPlacedComponent(
        ProjectPrintLayout $layout,
        int $row,
        int $position
    ): PrintLayoutComponents {
        $component = Component::create(['name' => 'X', 'type' => 'TextArea', 'data' => []]);

        return PrintLayoutComponents::create([
            'project_print_layout_id' => $layout->id,
            'component_id' => $component->id,
            'type' => 'body',
            'row' => $row,
            'position' => $position,
        ]);
    }
}
