<?php

namespace Tests\Feature\Modules\InventoryManagement;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CraftInventoryItemCellUploadTest extends FeatureTestCase
{
    #[Test]
    public function an_uploaded_file_is_stored_under_a_hash_and_keeps_its_readable_name(): void
    {
        $user = $this->adminUser();
        $cell = $this->uploadCell();

        $this->actingAs($user)->post(
            route('inventory-management.inventory.item-cell.update.cell-value.upload', $cell),
            ['file' => UploadedFile::fake()->create('Mein broken°^„Angebot 12:30.pdf', 10, 'application/pdf')]
        );

        $cell->refresh();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.[a-z0-9]+$/', $cell->cell_value);
        $this->assertSame('Mein broken°^„Angebot 12:30.pdf', $cell->file_original_name);
        Storage::assertExists('uploads/inventar/' . $cell->cell_value);
    }

    #[Test]
    public function two_uploads_of_the_same_filename_do_not_overwrite_each_other(): void
    {
        $user = $this->adminUser();
        $first = $this->uploadCell();
        $second = $this->uploadCell();

        foreach ([$first, $second] as $cell) {
            $this->actingAs($user)->post(
                route('inventory-management.inventory.item-cell.update.cell-value.upload', $cell),
                ['file' => UploadedFile::fake()->create('Rechnung.pdf', 10, 'application/pdf')]
            );
        }

        $first->refresh();
        $second->refresh();

        $this->assertNotSame($first->cell_value, $second->cell_value);
        Storage::assertExists('uploads/inventar/' . $first->cell_value);
        Storage::assertExists('uploads/inventar/' . $second->cell_value);
    }

    #[Test]
    public function the_download_refuses_a_cell_value_that_is_not_a_plain_filename(): void
    {
        $user = $this->adminUser();
        $cell = $this->uploadCell('../../../.env');

        $this->actingAs($user)
            ->get(route('inventory-management.inventory.item-cell.download', $cell))
            ->assertNotFound();
    }

    #[Test]
    public function deleting_refuses_a_cell_value_that_is_not_a_plain_filename(): void
    {
        $user = $this->adminUser();
        $cell = $this->uploadCell('../../../.env');

        $this->actingAs($user)
            ->delete(route('inventory-management.inventory.item-cell.update.cell-value.delete.file', $cell))
            ->assertNotFound();
    }

    #[Test]
    public function a_legacy_cell_value_is_still_downloadable_under_its_own_name(): void
    {
        $user = $this->adminUser();
        $cell = $this->uploadCell('Altbestand.pdf');
        Storage::put('uploads/inventar/Altbestand.pdf', '%PDF-1.4 test');

        $response = $this->actingAs($user)
            ->get(route('inventory-management.inventory.item-cell.download', $cell));

        $response->assertOk();
        $this->assertStringContainsString(
            'Altbestand.pdf',
            $response->headers->get('content-disposition', '')
        );
    }

    private function uploadCell(string $cellValue = ''): CraftInventoryItemCell
    {
        // The service stamps every write into the "last edit" column, so it has
        // to exist before a cell can be touched.
        CraftsInventoryColumn::query()->firstOrCreate(
            ['type' => CraftsInventoryColumnTypeEnum::LAST_EDIT_AND_EDITOR->value],
            ['name' => 'Last edit', 'type_options' => '[]', 'background_color' => '#fff', 'order' => 99]
        );

        $column = CraftsInventoryColumn::query()->create([
            'name' => 'File',
            'type' => CraftsInventoryColumnTypeEnum::UPLOAD->value,
            'type_options' => '[]',
            'background_color' => '#fff',
            'order' => 1,
        ]);

        $item = CraftInventoryItem::query()->forceCreate(['order' => 1]);

        $cell = CraftInventoryItemCell::query()->forceCreate([
            'crafts_inventory_column_id' => $column->id,
            'craft_inventory_item_id' => $item->id,
            'cell_value' => $cellValue,
        ]);

        CraftInventoryItemCell::query()->forceCreate([
            'crafts_inventory_column_id' => CraftsInventoryColumn::query()
                ->where('type', CraftsInventoryColumnTypeEnum::LAST_EDIT_AND_EDITOR->value)
                ->value('id'),
            'craft_inventory_item_id' => $item->id,
            'cell_value' => '',
        ]);

        return $cell;
    }
}
