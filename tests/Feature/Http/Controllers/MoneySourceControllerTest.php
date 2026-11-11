<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MoneySourceControllerTest extends FeatureTestCase
{
    #[Test]
    public function money_sources_index_ships_history_without_raw_activities_and_slim_causers(): void
    {
        $admin = $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        activity()->performedOn($source)->causedBy($admin)->withProperties([
            ['translationKey' => 'Money source created', 'type' => 'public_changes'],
        ])->log('created');

        $sources = collect($this->get(route('money_sources.index'))->assertOk()->viewData('page')['props']['moneySources']);
        $row = (array) $sources->firstWhere('id', $source->id);

        $this->assertArrayNotHasKey('activities', $row);
        $this->assertNotEmpty($row['history']);
        $changeBy = (array) $row['history'][0]['change_by'];
        $this->assertSame($admin->id, $changeBy['id']);
        $this->assertArrayNotHasKey('email', $changeBy);
    }

    #[Test]
    public function guest_cannot_view_money_sources_index(): void
    {
        $this->get(route('money_sources.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_money_sources_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('money_sources.index'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_view_money_sources_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('money_sources.settings'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_store_money_source(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('money_sources.store'), [
            'name' => 'New Source',
            'amount' => '5000',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'source_name' => 'Bank',
            'description' => 'Some desc',
            'is_group' => false,
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('money_sources', ['name' => 'New Source']);
    }

    #[Test]
    public function store_saves_the_group_chosen_in_the_create_dialog(): void
    {
        $this->actingAsAdmin();
        $group = MoneySource::factory()->create(['is_group' => true]);

        $this->post(route('money_sources.store'), [
            'name' => 'Source In Group',
            'amount' => '100',
            'is_group' => false,
            'group_id' => $group->id,
            'users' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('money_sources', ['name' => 'Source In Group', 'group_id' => $group->id]);
    }

    #[Test]
    public function index_ships_tasks_and_categories_for_groups_so_the_filters_can_match(): void
    {
        $this->actingAsAdmin();
        $group = MoneySource::factory()->create(['is_group' => true]);
        MoneySourceTask::factory()->create(['money_source_id' => $group->id, 'done' => false]);

        $props = $this->get(route('money_sources.index'))->assertOk()->viewData('page')['props'];
        $groupRow = (array) collect($props['moneySourceGroups'])->firstWhere('id', $group->id);
        $sourceRow = (array) collect($props['moneySources'])->firstWhere('id', $group->id);

        $this->assertArrayHasKey('categories', $groupRow);
        $this->assertCount(1, $groupRow['money_source_tasks']);
        $this->assertFalse(((array) $groupRow['money_source_tasks'][0])['done']);
        $this->assertFalse(((array) $sourceRow['money_source_tasks'][0])['done']);
    }

    #[Test]
    public function admin_can_destroy_money_source(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->delete('/money_sources/' . $source->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('money_sources', ['id' => $source->id]);
    }

    #[Test]
    public function admin_can_pin_money_source(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->post(route('money_sources.pin', $source));

        $response->assertRedirect();
    }

    #[Test]
    public function available_amount_keeps_cents_of_linked_cells(): void
    {
        // Zellwerte mit Dezimalkomma wurden per (int) abgeschnitten („1234,56“ → 1234).
        $this->actingAsAdmin();
        $moneySource = MoneySource::factory()->create(['is_group' => false, 'amount' => 1000.50]);
        $table = Table::factory()->create(['is_template' => false]);
        $column = Column::factory()->create(['table_id' => $table->id, 'position' => 3]);
        $row = SubPositionRow::factory()->create();
        ColumnCell::create([
            'column_id' => $column->id,
            'sub_position_row_id' => $row->id,
            'value' => '1234,56',
            'verified_value' => null,
            'commented' => false,
            'linked_money_source_id' => $moneySource->id,
            'linked_type' => 'EARNING',
        ]);

        $this->get(route('money_sources.show', $moneySource))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('moneySource.amount_available', fn ($value) => abs((float) $value - 2235.06) < 0.001));
    }
}
