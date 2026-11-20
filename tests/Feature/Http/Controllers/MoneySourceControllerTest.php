<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\MoneySource\Exports\MoneySourceListExport;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\User\Models\User;
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
    public function money_sources_index_ships_only_the_latest_history_entry(): void
    {
        $admin = $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        foreach (['Erste Änderung', 'Zweite Änderung', 'Dritte Änderung'] as $offset => $translationKey) {
            $activity = activity()->performedOn($source)->causedBy($admin)->withProperties([
                ['translationKey' => $translationKey, 'type' => 'public_changes'],
            ])->log('updated');
            $activity->forceFill(['created_at' => now()->subMinutes(10 - $offset)])->save();
        }

        $sources = collect($this->get(route('money_sources.index'))->assertOk()->viewData('page')['props']['moneySources']);
        $row = (array) $sources->firstWhere('id', $source->id);

        $this->assertCount(1, $row['history']);
        $this->assertSame('Dritte Änderung', $row['history'][0]['changes'][0]['translationKey']);
    }

    #[Test]
    public function export_lists_the_requested_sources_in_the_given_order(): void
    {
        $this->actingAsAdmin();
        $group = MoneySource::factory()->create(['name' => 'Spielzeit', 'is_group' => true, 'amount' => 0]);
        $first = MoneySource::factory()->create([
            'name' => 'Stiftung',
            'is_group' => false,
            'amount' => 1000,
            'group_id' => $group->id,
            'source_name' => 'Stadt',
            'funding_start_date' => '2026-01-01',
            'funding_end_date' => '2026-12-31',
        ]);
        $second = MoneySource::factory()->create(['name' => 'Sponsor', 'is_group' => false, 'amount' => 500]);
        MoneySource::factory()->create(['name' => 'Nicht exportiert', 'is_group' => false]);
        MoneySourceTask::factory()->create(['money_source_id' => $first->id, 'done' => false]);
        MoneySourceTask::factory()->create(['money_source_id' => $first->id, 'done' => true]);

        $export = new MoneySourceListExport(
            MoneySource::with(['categories', 'moneySourceTasks'])
                ->whereIn('id', [$second->id, $first->id])
                ->get()
                ->sortBy(fn (MoneySource $source): int => array_search($source->id, [$second->id, $first->id], true))
                ->values(),
            app(MoneySourceCalculationService::class)
        );
        $rows = $export->array();

        $this->assertCount(count($export->headings()), $rows[0]);
        $this->assertSame(['Sponsor', 'Stiftung'], array_column($rows, 0));
        $this->assertSame('Spielzeit', $rows[1][2]);
        $this->assertSame('Stadt', $rows[1][3]);
        $this->assertSame(1000.0, $rows[1][4]);
        $this->assertSame(1000.0, $rows[1][6]);
        $this->assertSame('01.01.2026', $rows[1][7]);
        $this->assertSame('31.12.2026', $rows[1][8]);
        $this->assertSame(1, $rows[1][12]);
        $this->assertNull($rows[0][2]);
    }

    #[Test]
    public function export_route_downloads_a_spreadsheet_and_requires_permission(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->get(route('money_sources.export', ['ids' => [$source->id]]))->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $this->actingAs(User::factory()->create());
        $this->get(route('money_sources.export'))->assertForbidden();
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
    public function store_rejects_a_single_source_as_group(): void
    {
        $this->actingAsAdmin();
        $singleSource = MoneySource::factory()->create(['is_group' => false]);

        $this->post(route('money_sources.store'), [
            'name' => 'Source In Single Source',
            'amount' => '100',
            'is_group' => false,
            'group_id' => $singleSource->id,
            'users' => [],
        ])->assertSessionHasErrors('group_id');

        $this->assertDatabaseMissing('money_sources', ['name' => 'Source In Single Source']);
    }

    #[Test]
    public function a_new_group_does_not_belong_to_another_group(): void
    {
        $this->actingAsAdmin();
        $group = MoneySource::factory()->create(['is_group' => true]);

        $this->post(route('money_sources.store'), [
            'name' => 'New Group',
            'amount' => '100',
            'is_group' => true,
            'group_id' => $group->id,
            'users' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('money_sources', ['name' => 'New Group', 'is_group' => true, 'group_id' => null]);
    }

    #[Test]
    public function update_keeps_a_group_out_of_other_groups(): void
    {
        $this->actingAsAdmin();
        $group = MoneySource::factory()->create(['is_group' => true]);
        $otherGroup = MoneySource::factory()->create(['is_group' => true]);

        $this->patch(route('money_sources.update', $otherGroup), [
            'name' => 'Other Group',
            'amount' => '100',
            'is_group' => true,
            'group_id' => $group->id,
            'users' => [],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('money_sources', ['id' => $otherGroup->id, 'group_id' => null]);
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
    public function unpinning_keeps_the_pins_of_other_users_as_a_list(): void
    {
        $admin = $this->actingAsAdmin();
        $source = MoneySource::factory()->create(['pinned_by_users' => [$admin->id, 999001, 999002]]);

        $this->post(route('money_sources.pin', $source))->assertRedirect();

        // Roh aus der Datenbank: eine JSON-Liste, kein Objekt mit Schlüsseln
        $this->assertSame(
            '[999001,999002]',
            str_replace(' ', '', (string) $source->fresh()->getRawOriginal('pinned_by_users'))
        );

        $this->post(route('money_sources.pin', $source))->assertRedirect();
        $this->assertSame([999001, 999002, $admin->id], $source->fresh()->pinned_by_users);
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
