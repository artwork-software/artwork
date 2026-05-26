<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BudgetGeneralControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_budget_general_index(): void
    {
        $this->get(route('budget-settings.general'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_budget_general_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('budget-settings.general'));

        $response->assertOk();
    }

    #[Test]
    public function user_without_permission_cannot_view_budget_general_index(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('budget-settings.general'));

        $response->assertForbidden();
    }

    #[Test]
    public function user_with_global_budget_permission_can_view_index(): void
    {
        $this->actingAsUserWith(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value);

        $response = $this->get(route('budget-settings.general'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_update_budget_column_setting(): void
    {
        $setting = BudgetColumnSetting::factory()->create(['column_name' => 'Original']);

        $this->patch(route('budget-settings.general.update', $setting), ['column_name' => 'Foo'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_budget_column_setting(): void
    {
        $this->actingAsAdmin();
        $setting = BudgetColumnSetting::factory()->create(['column_name' => 'Original']);

        $response = $this->patch(route('budget-settings.general.update', $setting), [
            'column_name' => 'Updated Name',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_column_settings', [
            'id' => $setting->id,
            'column_name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function user_without_permission_cannot_update_budget_column_setting(): void
    {
        $this->actingAs(User::factory()->create());
        $setting = BudgetColumnSetting::factory()->create(['column_name' => 'Original']);

        $response = $this->patch(route('budget-settings.general.update', $setting), [
            'column_name' => 'NoPerm',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('budget_column_settings', [
            'id' => $setting->id,
            'column_name' => 'Original',
        ]);
    }

    #[Test]
    public function guest_cannot_view_trashed_budget_tables(): void
    {
        $this->get(route('project.budget.trashed'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_trashed_budget_tables(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('project.budget.trashed'));

        $response->assertOk();
    }
}
