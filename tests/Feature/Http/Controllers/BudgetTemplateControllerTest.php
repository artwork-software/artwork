<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetTemplateControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_templates(): void
    {
        $this->get(route('budget-settings.templates'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_templates(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('budget-settings.templates'));

        $response->assertOk();
    }

    /**
     * Vorlagen übernehmen die Sage-Spalte nicht. Eine Summen- oder Differenzspalte, die sie
     * einbezieht, bleibt erhalten und rechnet in der Vorlage ohne den Sage-Anteil.
     */
    #[Test]
    public function template_is_created_when_a_sum_column_includes_the_sage_column(): void
    {
        $this->actingAsAdmin();

        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        $valueColumn = Column::factory()->create(['table_id' => $table->id, 'position' => 0]);
        $sageColumn = Column::factory()->create(['table_id' => $table->id, 'type' => 'sage', 'position' => 1]);
        $sumColumn = Column::factory()->create([
            'table_id' => $table->id,
            'type' => 'sum',
            'position' => 2,
            'linked_first_column' => $valueColumn->id,
            'linked_second_column' => $sageColumn->id,
        ]);
        $differenceColumn = Column::factory()->create([
            'table_id' => $table->id,
            'type' => 'difference',
            'position' => 3,
            'linked_first_column' => $sageColumn->id,
            'linked_second_column' => $valueColumn->id,
        ]);

        $mainPosition = MainPosition::factory()->create(['table_id' => $table->id]);
        $subPosition = SubPosition::factory()->create(['main_position_id' => $mainPosition->id]);
        $row = SubPositionRow::factory()->create(['sub_position_id' => $subPosition->id]);
        $cellValues = [
            $valueColumn->id => '100,00',
            $sageColumn->id => '0,00',
            $sumColumn->id => '150.00',
            $differenceColumn->id => '-50.00',
        ];
        foreach ($cellValues as $columnId => $value) {
            ColumnCell::factory()->create([
                'column_id' => $columnId,
                'sub_position_row_id' => $row->id,
                'value' => $value,
            ]);
        }

        $this->post(route('project.budget.template.create', $table->id), ['template_name' => 'Sage Vorlage'])
            ->assertRedirect();

        $template = Table::query()->where('name', 'Sage Vorlage')->where('is_template', true)->firstOrFail();
        $templateColumns = $template->columns()->orderBy('position')->get();

        $this->assertSame(['empty', 'sum', 'difference'], $templateColumns->pluck('type')->all());
        [$templateValue, $templateSum, $templateDifference] = $templateColumns->all();
        $this->assertSame($templateValue->id, $templateSum->linked_first_column);
        $this->assertNull($templateSum->linked_second_column);
        $this->assertNull($templateDifference->linked_first_column);
        $this->assertSame($templateValue->id, $templateDifference->linked_second_column);

        $templateCells = ColumnCell::query()
            ->whereIn('column_id', $templateColumns->pluck('id'))
            ->pluck('value', 'column_id');
        $this->assertSame('100,00', $templateCells[$templateValue->id]);
        $this->assertSame('100.00', $templateCells[$templateSum->id]);
        $this->assertSame('-100.00', $templateCells[$templateDifference->id]);
    }

    #[Test]
    public function template_keeps_the_link_of_a_sum_column_that_stands_before_its_source_columns(): void
    {
        $this->actingAsAdmin();

        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        $sumColumn = Column::factory()->create(['table_id' => $table->id, 'type' => 'sum', 'position' => 0]);
        $first = Column::factory()->create(['table_id' => $table->id, 'position' => 1]);
        $second = Column::factory()->create(['table_id' => $table->id, 'position' => 2]);
        $sumColumn->update(['linked_first_column' => $first->id, 'linked_second_column' => $second->id]);

        $this->post(route('project.budget.template.create', $table->id), ['template_name' => 'Reihenfolge'])
            ->assertRedirect();

        $template = Table::query()->where('name', 'Reihenfolge')->where('is_template', true)->firstOrFail();
        $templateColumns = $template->columns()->orderBy('position')->get();

        $this->assertSame($templateColumns[1]->id, $templateColumns[0]->linked_first_column);
        $this->assertSame($templateColumns[2]->id, $templateColumns[0]->linked_second_column);
    }
}
