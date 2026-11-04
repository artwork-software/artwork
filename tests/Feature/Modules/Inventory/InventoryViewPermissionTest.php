<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Permission\Catalog\PermissionCatalog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\FeatureTestCase;

/**
 * "Inventar sehen" öffnet die Inventarübersicht (alle Artikel, Kategorien, Unterkategorien), aber weder
 * Artikelplanung noch Materialausgaben. Ohne das Recht (oder ein Recht, das es enthält) bleibt die Übersicht zu.
 */
final class InventoryViewPermissionTest extends FeatureTestCase
{
    private InventoryCategory $category;
    private InventorySubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(ModuleSettings::class);
        $settings->inventory = true;
        $settings->save();

        $this->category = InventoryCategory::factory()->create();
        $this->subCategory = InventorySubCategory::factory()->create([
            'inventory_category_id' => $this->category->id,
        ]);
    }

    #[Test]
    public function withoutAnyInventoryPermissionTheOverviewIsForbidden(): void
    {
        $this->actingAsUserWith([]);

        foreach ($this->overviewUrls() as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    #[Test]
    public function viewPermissionOpensTheOverviewButNotPlanningOrMaterialIssues(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_VIEW->value);

        foreach ($this->overviewUrls() as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('inventory-management.article.planning'))->assertForbidden();
        $this->get(route('issue-of-material.index'))->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function permissionsThatIncludeViewing(): array
    {
        return [
            'create & edit' => [PermissionEnum::INVENTORY_CREATE_EDIT->value],
            'delete' => [PermissionEnum::INVENTORY_DELETE->value],
            'disposition' => [PermissionEnum::INVENTORY_DISPOSITION->value],
        ];
    }

    #[Test]
    #[DataProvider('permissionsThatIncludeViewing')]
    public function strongerInventoryPermissionsAlsoOpenTheOverview(string $permission): void
    {
        $this->actingAsUserWith($permission);

        $this->get(route('inventory.index'))->assertOk();
    }

    #[Test]
    public function settingsPermissionAloneDoesNotOpenTheOverview(): void
    {
        $this->actingAsUserWith(PermissionEnum::INVENTORY_SETTINGS->value);

        $this->get(route('inventory.index'))->assertForbidden();
    }

    #[Test]
    public function adminsSeeTheOverviewWithoutTheExplicitPermission(): void
    {
        $this->actingAsAdmin();

        $this->get(route('inventory.index'))->assertOk();
    }

    #[Test]
    public function catalogSetsViewTogetherWithStrongerInventoryPermissions(): void
    {
        $catalog = app(PermissionCatalog::class);

        foreach (self::permissionsThatIncludeViewing() as [$permission]) {
            $this->assertContains(
                PermissionEnum::INVENTORY_VIEW->value,
                $catalog->expandWithImplied([$permission]),
                $permission . ' muss "Inventar sehen" mitsetzen'
            );
        }
    }

    #[Test]
    public function migrationGrantsViewToAllExistingUsers(): void
    {
        $existing = $this->actingAsUserWith([]);
        $other = User::factory()->create();
        DB::table('permissions')->where('name', PermissionEnum::INVENTORY_VIEW->value)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $migration = require database_path(
            'migrations/2026_11_04_155853_grant_inventory_view_permission_to_existing_users.php'
        );
        $migration->up();
        // Zweiter Lauf darf weder doppelt anlegen noch scheitern
        $migration->up();

        $this->assertSame(
            1,
            DB::table('permissions')->where('name', PermissionEnum::INVENTORY_VIEW->value)->count()
        );
        foreach ([$existing, $other] as $user) {
            $this->assertTrue(
                $user->fresh()->hasPermissionTo(PermissionEnum::INVENTORY_VIEW->value),
                'Bestandsperson ' . $user->id . ' muss "Inventar sehen" bekommen'
            );
        }
    }

    /** @return string[] */
    private function overviewUrls(): array
    {
        return [
            route('inventory.index'),
            route('inventory.category.show', ['inventoryCategory' => $this->category->id]),
            route('inventory.sub.category.show', [
                'inventoryCategory' => $this->category->id,
                'inventorySubCategory' => $this->subCategory->id,
            ]),
        ];
    }
}
