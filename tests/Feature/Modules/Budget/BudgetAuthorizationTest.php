<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetAuthorizationTest extends FeatureTestCase
{
    private function createProjectTableWithColumn(): array
    {
        $table = Table::factory()->create(['is_template' => false]);
        foreach ([0, 1, 2] as $position) {
            Column::factory()->create(['table_id' => $table->id, 'position' => $position]);
        }
        $column = Column::factory()->create([
            'table_id' => $table->id,
            'position' => 3,
            'relevant_for_project_groups' => true,
        ]);

        return [$table->refresh(), $column];
    }

    #[Test]
    public function user_without_budget_access_cannot_mutate_budget(): void
    {
        [$table, $column] = $this->createProjectTableWithColumn();
        $this->actingAs(User::factory()->create());

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ])->assertForbidden();

        $this->delete(route('project.budget.column.delete', ['column' => $column->id]))
            ->assertForbidden();

        $this->patch(route('project.budget.cell.update'), [
            'column_id' => $column->id,
            'sub_position_row_id' => 1,
            'value' => '100',
        ])->assertForbidden();
    }

    #[Test]
    public function user_with_project_budget_access_can_mutate_budget(): void
    {
        [$table] = $this->createProjectTableWithColumn();
        $user = User::factory()->create();
        $table->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ])->assertSuccessful();
    }

    #[Test]
    public function budget_access_on_one_project_does_not_grant_access_to_another(): void
    {
        [$tableA] = $this->createProjectTableWithColumn();
        [$tableB] = $this->createProjectTableWithColumn();

        $user = User::factory()->create();
        $tableA->project->users()->attach($user->id, ['access_budget' => true]);
        $this->actingAs($user);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $tableB->id,
            'column_type' => 'empty',
        ])->assertForbidden();
    }

    #[Test]
    public function user_with_global_budget_permission_can_mutate_any_budget(): void
    {
        [$table] = $this->createProjectTableWithColumn();
        $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value);

        $this->post(route('project.budget.column.add'), [
            'table_id' => $table->id,
            'column_type' => 'empty',
        ])->assertSuccessful();
    }
}
