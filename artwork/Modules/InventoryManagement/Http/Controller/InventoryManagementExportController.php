<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Export\CreateInventoryManagementExportRequest;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementExportService;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryManagementExportController extends Controller
{
    public function __construct(private readonly InventoryManagementExportService $inventoryManagementExportService)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function cacheExportData(CreateInventoryManagementExportRequest $request): string
    {
        return $this->inventoryManagementExportService
            ->cacheRequestData($request->collect('data'));
    }

    /**
     */
    public function downloadXlsx(string $cacheToken): BinaryFileResponse
    {
        return $this->inventoryManagementExportService
            ->createXlsxExport($cacheToken)
            ->download($this->inventoryManagementExportService->createXlsxExportFilename())
            ->deleteFileAfterSend();
    }
}
