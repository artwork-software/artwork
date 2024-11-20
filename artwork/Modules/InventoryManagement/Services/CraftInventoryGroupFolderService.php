<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroupFolder;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryGroupFolderRepository;

readonly class CraftInventoryGroupFolderService
{
    public function __construct(
        private CraftsInventoryGroupFolderRepository $craftsInventoryGroupFolderRepository
    ) {
    }

    public function create(
        int $groupId,
        string $name
    ): CraftInventoryGroupFolder {
        $craftsInventoryGroupFolder = $this->craftsInventoryGroupFolderRepository->getNewModelInstance(
            [
                'craft_inventory_group_id' => $groupId,
                'name' => $name,
                'order' => CraftInventoryGroupFolder::max('order') + 1
            ]
        );
        $this->craftsInventoryGroupFolderRepository->saveOrFail($craftsInventoryGroupFolder);

        return $craftsInventoryGroupFolder;
    }
}
