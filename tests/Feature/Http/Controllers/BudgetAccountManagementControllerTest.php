<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetAccountManagementControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_account_management(): void
    {
        $this->get(route('budget-settings.account-management'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_account_management(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('budget-settings.account-management'));

        $response->assertOk();
    }

    #[Test]
    public function user_without_permission_cannot_view_account_management(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('budget-settings.account-management'));

        $response->assertForbidden();
    }

    #[Test]
    public function user_with_global_budget_permission_can_view_account_management(): void
    {
        $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value);

        $response = $this->get(route('budget-settings.account-management'));

        $response->assertOk();
    }
}
