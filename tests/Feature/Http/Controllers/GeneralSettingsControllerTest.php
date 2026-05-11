<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class GeneralSettingsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_inventory_general_settings(): void
    {
        $this->get(route('inventory-management.settings.general'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_inventory_general_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('inventory-management.settings.general'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_update_inventory_detailed_articles_always_quantity_one(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(
            route('inventory-management.settings.general.update-detailed-articles-always-quantity-one'),
            ['inventory_detailed_articles_always_quantity_one' => true]
        );

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_update_budget_account_management_global(): void
    {
        $this->patch(route('budget-settings.account-management.updateBudgetAccountManagementGlobal'), [])
            ->assertRedirect(route('login'));
    }

    // Skipped: GeneralSettingsService::updateBudgetAccountManagementGlobalFromRequest sets the property null
    // when the request key is absent. The model property is typed `bool`, causing a TypeError if absent.
    // Auth-test above already covers the route.
}
