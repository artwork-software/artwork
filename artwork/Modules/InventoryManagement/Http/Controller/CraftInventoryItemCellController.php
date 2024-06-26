<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\ItemCell\UpdateCraftInventoryItemCellCellValueRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryItemCellController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryItemCellService $craftInventoryItemCellService
    ) {
    }

    public function updateCellValue(
        CraftInventoryItemCell $craftInventoryItemCell,
        UpdateCraftInventoryItemCellCellValueRequest $request
    ): RedirectResponse {
        $cellValue = $request->get('cell_value');

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
                ->with('error', 'Wert konnte nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
}
