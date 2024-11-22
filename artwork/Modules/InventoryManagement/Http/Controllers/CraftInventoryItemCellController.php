<?php

namespace Artwork\Modules\InventoryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadInventoryFileRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\ItemCell\UpdateCraftInventoryItemCellCellValueRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryItemCellController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryItemCellService $craftInventoryItemCellService,
        private readonly Translator $translator
    ) {
    }

    public function updateCellValue(
        CraftInventoryItemCell $craftInventoryItemCell,
        UpdateCraftInventoryItemCellCellValueRequest $request
    ): RedirectResponse {
        $cellValue = $request->string('cell_value');

        try {
            $this->craftInventoryItemCellService->updateCellValue($cellValue, $craftInventoryItemCell);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory cell value to: "%s" for reason: "%s"',
                    $cellValue,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.item-cell.errors.updateCellValue')
                );
        }

        return $this->redirector->back();
    }

    public function updateCellValueUpload(
        CraftInventoryItemCell $craftInventoryItemCell,
        UploadInventoryFileRequest $request
    ): void {
        $cellValue = $request->string('cell_value');
        $this->craftInventoryItemCellService->updateCellValue($cellValue, $craftInventoryItemCell);

        $file = $request->file('file');

        $file->storeAs('uploads/inventar', $file->getClientOriginalName());

        $this->redirector->back();
    }

    public function getDownloadCellValueUpload(
        CraftInventoryItemCell $craftInventoryItemCell
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        return Storage::download('uploads/inventar/' . $craftInventoryItemCell->cell_value);
    }

    public function removeUploadedFile(CraftInventoryItemCell $craftInventoryItemCell): RedirectResponse
    {
        Storage::delete('uploads/inventar/' . $craftInventoryItemCell->cell_value);
        $this->craftInventoryItemCellService->updateCellValue('', $craftInventoryItemCell);

        return $this->redirector->back();
    }
}
