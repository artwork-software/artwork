<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Exports\InventoryManagementXlsxExport;
use Carbon\Carbon;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

class InventoryManagementExportService
{

    public function __construct(private readonly CacheManager $cacheManager)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function cacheRequestData(Collection $data): string
    {
        $token = Str::random(128);

        $this->cacheManager->set($token, $data, 20);

        return $token;
    }

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

    public function createXlsxExport(string $token): InventoryManagementXlsxExport
    {
        return new InventoryManagementXlsxExport($this->getCachedRequestData($token));
    }
}
