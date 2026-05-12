<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetManagementCostUnitControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_cost_unit(): void
    {
        $this->post(route('budget-settings.account-management.store-cost-unit'), [
            'cost_unit_number' => '500',
            'title' => 'Foo',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_cost_unit(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('budget-settings.account-management.store-cost-unit'), [
            'cost_unit_number' => '500',
            'title' => 'Test Cost Unit',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_management_cost_units', [
            'cost_unit_number' => '500',
            'title' => 'Test Cost Unit',
        ]);
    }

    #[Test]
    public function admin_can_update_cost_unit(): void
    {
        $this->actingAsAdmin();
        $costUnit = BudgetManagementCostUnit::factory()->create(['title' => 'Old']);

        $response = $this->patch(
            route('budget-settings.account-management.update-cost-unit', $costUnit),
            ['cost_unit_number' => $costUnit->cost_unit_number, 'title' => 'Updated']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_management_cost_units', [
            'id' => $costUnit->id,
            'title' => 'Updated',
        ]);
    }

    #[Test]
    public function admin_can_destroy_cost_unit(): void
    {
        $this->actingAsAdmin();
        $costUnit = BudgetManagementCostUnit::factory()->create();

        $response = $this->delete(
            route('budget-settings.account-management.destroy-cost-unit', $costUnit)
        );

        $response->assertRedirect();
        $this->assertSoftDeleted('budget_management_cost_units', ['id' => $costUnit->id]);
    }

    #[Test]
    public function admin_can_view_trashed_cost_units(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('budget-settings.account-management.trash-cost-units'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_search_cost_units(): void
    {
        $this->actingAsAdmin();
        BudgetManagementCostUnit::factory()->create(['title' => 'Searchable Unit']);

        $response = $this->get(
            route('budget-settings.account-management.search-cost-units') . '?search=Searchable'
        );

        $response->assertOk();
    }

    #[Test]
    public function user_without_permission_cannot_store_cost_unit(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('budget-settings.account-management.store-cost-unit'), [
            'cost_unit_number' => '500',
            'title' => 'Foo',
        ]);

        $response->assertForbidden();
    }
}
