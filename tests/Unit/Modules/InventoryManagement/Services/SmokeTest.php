<?php

namespace Tests\Unit\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupFolderService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Artwork\Modules\InventoryManagement\Services\InventoryResourceCalculateModelsOrderService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SmokeTest extends TestCase
{
    public static function services(): array
    {
        return [
            'craft inventory category' => [CraftInventoryCategoryService::class],
            'craft inventory group folder' => [CraftInventoryGroupFolderService::class],
            'craft inventory group' => [CraftInventoryGroupService::class],
            'craft inventory item' => [CraftInventoryItemService::class],
            'crafts inventory column' => [CraftsInventoryColumnService::class],
            'inventory management user filter' => [InventoryManagementUserFilterService::class],
            'inventory resource calculate models order' => [InventoryResourceCalculateModelsOrderService::class],
        ];
    }

    #[Test]
    #[DataProvider('services')]
    public function service_can_be_resolved_from_container(string $serviceClass): void
    {
        $service = app($serviceClass);

        $this->assertInstanceOf($serviceClass, $service);
    }
}
