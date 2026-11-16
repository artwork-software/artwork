<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/** Die Papierkorb-Routen banden dank withTrashed auch aktive Konten/Kostenträger und löschten sie endgültig. */
final class BudgetManagementForceDeleteActiveTest extends FeatureTestCase
{
    #[Test]
    public function active_entries_are_not_force_deleted_through_the_trash_routes(): void
    {
        $this->actingAsAdmin();
        $account = BudgetManagementAccount::factory()->create();
        $costUnit = BudgetManagementCostUnit::factory()->create();

        $this->delete(route('budget-settings.account-management.trash-accounts.forceDelete', $account->id))
            ->assertNotFound();
        $this->delete(route('budget-settings.account-management.trash-cost-units.forceDelete', $costUnit->id))
            ->assertNotFound();

        self::assertNotNull(BudgetManagementAccount::find($account->id));
        self::assertNotNull(BudgetManagementCostUnit::find($costUnit->id));
    }
}
