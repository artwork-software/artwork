<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Exports\InventoryManagementExport;
use Carbon\Carbon;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

readonly class InventoryManagementExportService
{
    public function __construct(
        private ViewFactory $viewFactory,
        private CacheManager $cacheManager,
        private CraftsInventoryColumnService $craftsInventoryColumnService
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function cacheRequestData(Collection $data): string
    {
        $token = Str::random(128);

        //cache forgets the item after 10 seconds, time enough to download
        $this->cacheManager->set($token, $data, 10);

        return $token;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getCachedRequestData(string $token): Collection
    {
        $data = $this->cacheManager->get($token);

        $this->cacheManager->delete($token);

        return $data;
    }

    public function createXlsxExportFilename(): string
    {
        return sprintf(
            'artwork_inventory_management_%s.xlsx',
            Carbon::now()->format('d-m-Y_H_i_s')
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createExport(string $token): InventoryManagementExport
    {
        return new InventoryManagementExport(
            $this->viewFactory,
            $this->craftsInventoryColumnService->getAllOrdered(),
            $this->getCachedRequestData($token)
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
