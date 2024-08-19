<?php

namespace Artwork\Modules\InventoryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Export\CreateInventoryManagementExportRequest;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use Maatwebsite\Excel\Excel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class InventoryManagementExportController extends Controller
{
    public function __construct(
        private readonly InventoryManagementExportService $inventoryManagementExportService,
        private readonly Redirector $redirector,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator
    ) {
    }

    /**
     * @throws Throwable
     */
    public function saveExportDataInCache(CreateInventoryManagementExportRequest $request): string
    {
        try {
            //used for any type of export (pdf, xlsx)
            return $this->inventoryManagementExportService->cacheRequestData($request->collect('data'));
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not cache export data for reason "%s"', $t->getMessage()));
            //throw for axios for proper handling with .catch()
            throw $t;
        }
    }

    public function downloadXlsx(string $cacheToken): RedirectResponse|BinaryFileResponse
    {
        try {
            return $this->inventoryManagementExportService
                ->getConfiguredExport($cacheToken)
                ->download($this->inventoryManagementExportService->createXlsxExportFilename())
                ->deleteFileAfterSend();
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not create xlsx export for reason "%s"', $t->getMessage()));
            return $this->redirector
                ->route('inventory-management.inventory')
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.export.errors.download')
                );
        }
    }

    public function downloadPdf(string $cacheToken): RedirectResponse|BinaryFileResponse
    {
        try {
            return $this->inventoryManagementExportService
                ->getConfiguredExport($cacheToken)
                ->download($this->inventoryManagementExportService->createPdfExportFilename(), Excel::DOMPDF)
                ->deleteFileAfterSend();
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Could not create pdf export for reason "%s"', $t->getMessage()));
            return $this->redirector
                ->route('inventory-management.inventory')
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.export.errors.download')
                );
        }
    }
}
