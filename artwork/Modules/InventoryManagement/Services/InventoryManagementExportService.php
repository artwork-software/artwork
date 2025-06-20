<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Core\Services\CacheService;
use Artwork\Modules\Inventory\Exports\InventoryManagementExport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class InventoryManagementExportService
{
    public function __construct(
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly InventoryManagementExport $inventoryManagementExport,
        private readonly CacheService $cacheService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function cacheRequestData(Collection $data): string
    {
        return $this->cacheService->setValueAndGetCacheTokenValidForTenSeconds($data);
    }

    /**
     * @throws Throwable
     */
    public function getCachedRequestData(string $token): Collection
    {
        return $this->cacheService->getValueByToken($token);
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
