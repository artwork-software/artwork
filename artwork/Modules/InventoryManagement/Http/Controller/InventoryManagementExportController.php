<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Export\CreateInventoryManagementExportRequest;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryManagementExportController extends Controller
{
    public function __construct(private readonly InventoryManagementExportService $inventoryManagementExportService)
    {
    }

    public function xlsx(CreateInventoryManagementExportRequest $request): BinaryFileResponse
    {
        dd($request->get('data'));
        $this->inventoryManagementExportService->createXlsx();
    }

    public function pdf(): BinaryFileResponse
    {

    }
}
