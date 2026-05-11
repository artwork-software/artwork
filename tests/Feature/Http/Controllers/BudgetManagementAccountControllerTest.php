<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetManagementAccountControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_account(): void
    {
        $this->post(route('budget-settings.account-management.store-account'), [
            'account_number' => '1234',
            'title' => 'Foo',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_account(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('budget-settings.account-management.store-account'), [
            'account_number' => '1234',
            'title' => 'Test Account',
            'is_account_for_revenue' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_management_accounts', [
            'account_number' => '1234',
            'title' => 'Test Account',
        ]);
    }

    #[Test]
    public function admin_can_update_account(): void
    {
        $this->actingAsAdmin();
        $account = BudgetManagementAccount::factory()->create(['title' => 'Old']);

        $response = $this->patch(
            route('budget-settings.account-management.update-account', $account),
            ['account_number' => $account->account_number, 'title' => 'New Title']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_management_accounts', [
            'id' => $account->id,
            'title' => 'New Title',
        ]);
    }

    #[Test]
    public function admin_can_destroy_account(): void
    {
        $this->actingAsAdmin();
        $account = BudgetManagementAccount::factory()->create();

        $response = $this->delete(
            route('budget-settings.account-management.destroy-account', $account)
        );

        $response->assertRedirect();
        $this->assertSoftDeleted('budget_management_accounts', ['id' => $account->id]);
    }

    #[Test]
    public function admin_can_view_trashed_accounts(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('budget-settings.account-management.trash-accounts'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_restore_account(): void
    {
        $this->actingAsAdmin();
        $account = BudgetManagementAccount::factory()->create();
        $account->delete();

        $response = $this->patch(
            route('budget-settings.account-management.trash-accounts.restore', $account->id)
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_management_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function admin_can_search_accounts(): void
    {
        $this->actingAsAdmin();
        BudgetManagementAccount::factory()->create(['title' => 'Searchable Title']);

        $response = $this->get(
            route('budget-settings.account-management.search-accounts') . '?search=Searchable'
        );

        $response->assertOk();
    }

    #[Test]
    public function user_without_permission_cannot_store_account(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('budget-settings.account-management.store-account'), [
            'account_number' => '1234',
            'title' => 'Test',
        ]);

        $response->assertForbidden();
    }
}
