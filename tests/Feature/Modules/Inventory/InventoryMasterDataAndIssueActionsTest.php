<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\MaterialSet\Models\MaterialSetItem;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für Hersteller, Materialsets, Rückgabe-Aktionen externer Ausgaben,
 * Sonderposten-Abschluss und die Anzeigeeinstellungen des Inventars.
 */
final class InventoryMasterDataAndIssueActionsTest extends FeatureTestCase
{
    #[Test]
    public function manufacturer_can_be_stored_updated_and_destroyed(): void
    {
        $this->actingAsAdmin();

        $this->post(route('manufacturers.store'), [
            'name' => 'Bühnenbau GmbH',
            'email' => 'info@buehnenbau.test',
            'customer_number' => 'K-42',
        ])->assertOk();

        $manufacturer = Manufacturer::query()->where('name', 'Bühnenbau GmbH')->firstOrFail();
        $this->assertSame('K-42', $manufacturer->customer_number);

        $this->patch(route('manufacturers.update', $manufacturer), [
            'id' => $manufacturer->id,
            'name' => 'Bühnenbau AG',
            'email' => null,
        ])->assertOk();

        $manufacturer->refresh();
        $this->assertSame('Bühnenbau AG', $manufacturer->name);
        $this->assertNull($manufacturer->email);

        $this->delete(route('manufacturers.destroy', $manufacturer))->assertOk();
        $this->assertModelMissing($manufacturer);
    }

    #[Test]
    public function manufacturer_store_and_update_validate_input(): void
    {
        $this->actingAsAdmin();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Alt']);

        $this->postJson(route('manufacturers.store'), ['name' => '', 'phone' => str_repeat('1', 300)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone']);

        $this->patchJson(route('manufacturers.update', $manufacturer), ['name' => str_repeat('x', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id', 'name']);

        $this->assertSame('Alt', $manufacturer->fresh()->name);
    }

    #[Test]
    public function material_set_update_replaces_items_and_destroy_removes_set_with_items(): void
    {
        $this->actingAsAdmin();
        $set = MaterialSet::factory()->create(['name' => 'Alt']);
        $oldItem = MaterialSetItem::factory()->create(['material_set_id' => $set->id, 'quantity' => 1]);
        $articleA = InventoryArticle::factory()->create();
        $articleB = InventoryArticle::factory()->create();

        $this->patch(route('material-sets.update', $set), [
            'name' => 'Lichtset',
            'description' => 'Grundausstattung',
            'items' => [
                ['id' => $articleA->id, 'quantity' => 2],
                ['id' => $articleB->id, 'quantity' => 5],
            ],
        ])->assertOk();

        $set->refresh();
        $this->assertSame('Lichtset', $set->name);
        $this->assertModelMissing($oldItem);
        $this->assertEqualsCanonicalizing(
            [[$articleA->id, 2], [$articleB->id, 5]],
            $set->items()->get()->map(fn ($item) => [$item->inventory_article_id, $item->quantity])->all()
        );

        $this->delete(route('material-sets.destroy', $set))->assertOk();
        $this->assertModelMissing($set);
        $this->assertDatabaseMissing('material_set_items', ['material_set_id' => $set->id]);
    }

    #[Test]
    public function material_set_update_validates_items_and_keeps_existing_items(): void
    {
        $this->actingAsAdmin();
        $set = MaterialSet::factory()->create(['name' => 'Alt']);
        $item = MaterialSetItem::factory()->create(['material_set_id' => $set->id, 'quantity' => 3]);

        $this->patchJson(route('material-sets.update', $set), ['name' => 'Neu', 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->patchJson(route('material-sets.update', $set), [
            'name' => 'Neu',
            'items' => [['id' => 99999999, 'quantity' => 0]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.id', 'items.0.quantity']);

        $this->assertSame('Alt', $set->fresh()->name);
        $this->assertModelExists($item);
    }

    #[Test]
    public function confirming_a_return_sets_status_receiver_remarks_and_caps_return_date(): void
    {
        Carbon::setTestNow('2026-11-16 10:00:00');
        $user = $this->actingAsAdmin();
        $issue = ExternalIssue::factory()->create([
            'issue_date' => '2026-11-01',
            'return_date' => '2026-12-31',
            'return_status' => null,
            'received_by_id' => null,
        ]);

        $this->from('/issues')
            ->post(route('extern-issue-of-material.return', $issue), ['return_remarks' => 'Kabel fehlt'])
            ->assertRedirect('/issues');

        $issue->refresh();
        $this->assertSame(ExternalIssue::RETURN_STATUS_RETURNED, $issue->return_status);
        $this->assertSame($user->id, $issue->received_by_id);
        $this->assertSame('Kabel fehlt', $issue->return_remarks);
        $this->assertSame('2026-11-16', $issue->return_date->toDateString());
    }

    #[Test]
    public function confirming_a_return_without_remarks_field_keeps_existing_remarks_and_receiver(): void
    {
        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->actingAsAdmin();
        $receiver = User::factory()->create();
        $issue = ExternalIssue::factory()->create([
            'issue_date' => '2026-11-01',
            'return_date' => '2026-11-10',
            'return_remarks' => 'Alte Bemerkung',
            'received_by_id' => $receiver->id,
        ]);

        $this->post(route('extern-issue-of-material.return', $issue))->assertRedirect();

        $issue->refresh();
        $this->assertSame('Alte Bemerkung', $issue->return_remarks);
        $this->assertSame($receiver->id, $issue->received_by_id);
        // Rückgabedatum in der Vergangenheit bleibt unverändert
        $this->assertSame('2026-11-10', $issue->return_date->toDateString());
    }

    #[Test]
    public function confirming_a_return_before_the_issue_date_never_reverses_the_period(): void
    {
        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->actingAsAdmin();
        $issue = ExternalIssue::factory()->create([
            'issue_date' => '2026-11-20',
            'return_date' => '2026-11-30',
        ]);

        $this->post(route('extern-issue-of-material.return', $issue))->assertRedirect();

        $this->assertSame('2026-11-20', $issue->fresh()->return_date->toDateString());
    }

    #[Test]
    public function an_empty_remarks_field_clears_previous_remarks(): void
    {
        $this->actingAsAdmin();
        $issue = ExternalIssue::factory()->create(['return_remarks' => 'Alt']);

        $this->post(route('extern-issue-of-material.return', $issue), ['return_remarks' => ''])->assertRedirect();

        $this->assertNull($issue->fresh()->return_remarks);
    }

    #[Test]
    public function declining_a_return_marks_the_issue_as_not_returned(): void
    {
        $this->actingAsAdmin();
        $issue = ExternalIssue::factory()->create(['return_status' => null]);

        $this->from('/issues')
            ->post(route('extern-issue-of-material.return-decline', $issue))
            ->assertRedirect('/issues');

        $this->assertSame(ExternalIssue::RETURN_STATUS_NOT_RETURNED, $issue->fresh()->return_status);
    }

    #[Test]
    public function issuing_person_without_disposition_right_can_confirm_return(): void
    {
        $issuer = User::factory()->create();
        $this->actingAs($issuer);
        $issue = ExternalIssue::factory()->create(['issued_by_id' => $issuer->id]);

        $this->post(route('extern-issue-of-material.return', $issue))->assertRedirect();

        $this->assertSame(ExternalIssue::RETURN_STATUS_RETURNED, $issue->fresh()->return_status);
    }

    #[Test]
    public function special_items_of_external_and_internal_issues_can_be_marked_done(): void
    {
        $this->actingAsAdmin();
        $external = ExternalIssue::factory()->create(['special_items_done' => false]);
        $internal = InternalIssue::factory()->create(['special_items_done' => false]);

        $this->post(route('extern-issue-of-material.set-special-items-done', $external))
            ->assertRedirect(route('extern-issue-of-material.index'));
        $this->from('/internal')
            ->post(route('issue-of-material.set-special-items-done', $internal))
            ->assertRedirect('/internal');

        $this->assertTrue((bool) $external->fresh()->special_items_done);
        $this->assertTrue((bool) $internal->fresh()->special_items_done);
    }

    #[Test]
    public function deleting_an_external_issue_file_removes_record_and_stored_file(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');
        Storage::disk('local')->put('external_material_issues/beleg.pdf', 'pdf');
        $file = ExternalIssueFile::factory()->create(['file_path' => 'external_material_issues/beleg.pdf']);

        $this->deleteJson(route('extern-issue-of-material.file.delete', $file))
            ->assertOk()
            ->assertJson(['message' => 'File deleted successfully']);

        $this->assertModelMissing($file);
        Storage::disk('local')->assertMissing('external_material_issues/beleg.pdf');
    }

    #[Test]
    public function deleting_an_external_issue_file_whose_stored_file_is_gone_still_succeeds(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');
        $file = ExternalIssueFile::factory()->create(['file_path' => 'external_material_issues/weg.pdf']);

        $this->deleteJson(route('extern-issue-of-material.file.delete', $file))->assertOk();

        $this->assertModelMissing($file);
    }

    #[Test]
    public function inventory_display_settings_are_saved(): void
    {
        $this->actingAsAdmin();

        $this->from('/settings')->patch(
            route('inventory-management.settings.general.update-inventory-display-settings'),
            ['inventory_show_inventory_number_as_name' => true, 'inventory_number_prefix' => 'INV-']
        )->assertRedirect('/settings');

        $settings = app(GeneralSettings::class)->refresh();
        $this->assertTrue($settings->inventory_show_inventory_number_as_name);
        $this->assertSame('INV-', $settings->inventory_number_prefix);

        $this->patch(
            route('inventory-management.settings.general.update-inventory-display-settings'),
            ['inventory_show_inventory_number_as_name' => false, 'inventory_number_prefix' => '']
        )->assertRedirect();

        $settings = app(GeneralSettings::class)->refresh();
        $this->assertFalse($settings->inventory_show_inventory_number_as_name);
        $this->assertSame('', $settings->inventory_number_prefix);
    }

    #[Test]
    public function inventory_display_settings_reject_invalid_input_instead_of_failing(): void
    {
        $this->actingAsAdmin();
        $settings = app(GeneralSettings::class);
        $settings->inventory_number_prefix = 'ALT-';
        $settings->save();

        $this->patchJson(
            route('inventory-management.settings.general.update-inventory-display-settings'),
            ['inventory_show_inventory_number_as_name' => 'ja', 'inventory_number_prefix' => ['INV-']]
        )->assertUnprocessable()->assertJsonValidationErrors([
            'inventory_show_inventory_number_as_name',
            'inventory_number_prefix',
        ]);

        $this->patchJson(
            route('inventory-management.settings.general.update-inventory-display-settings'),
            ['inventory_show_inventory_number_as_name' => false, 'inventory_number_prefix' => str_repeat('x', 300)]
        )->assertUnprocessable()->assertJsonValidationErrors('inventory_number_prefix');

        $this->assertSame('ALT-', app(GeneralSettings::class)->refresh()->inventory_number_prefix);
    }
}
