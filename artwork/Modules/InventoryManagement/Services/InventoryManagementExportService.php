<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Exports\InventoryManagementExport;
use Carbon\Carbon;
use DragonCode\Support\Helpers\Str;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Throwable;

class InventoryManagementExportService
{
    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly InventoryManagementExport $inventoryManagementExport,
        private readonly Str $str
    ) {
    }

    /**
     * @throws Throwable
     */
    public function cacheRequestData(Collection $data): string
    {
        //cache forgets the item after 10 seconds, time enough to download
        $this->cacheManager->set($token = $this->str->random(128), $data, 10);

        return $token;
    }

    /**
     * @throws Throwable
     */
    public function getCachedRequestData(string $token): Collection
    {
        $data = $this->cacheManager->get($token);

        $this->cacheManager->delete($token);

        return $data;
    }

    /**
     * @throws Throwable
     */
    public function getConfiguredExport(string $token): InventoryManagementExport
    {
        return $this->inventoryManagementExport
            ->setColumns($this->craftsInventoryColumnService->getAllOrdered())
            ->setCrafts($this->getCachedRequestData($token));
    }

    public function createXlsxExportFilename(): string
    {
        return sprintf(
            'artwork_inventory_management_%s.xlsx',
            Carbon::now()->format('d-m-Y_H_i_s')
        );
    }

    public function createPdfExportFilename(): string
    {
        return sprintf(
            'artwork_inventory_management_%s.pdf',
            Carbon::now()->format('d-m-Y_H_i_s')
        );
    }
}
