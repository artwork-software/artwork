<?php

namespace Artwork\Modules\InventoryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadInventoryFileRequest;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\InventoryManagement\Http\Requests\ItemCell\UpdateCraftInventoryItemCellCellValueRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $file = $request->file('file');

        // The stored name comes from the file, never from the request - cell_value
        // is interpolated into a storage path further down.
        $storedName = StoredFileName::forUpload($file);

        $file->storeAs('uploads/inventar', $storedName);

        $this->craftInventoryItemCellService->updateCellValue($storedName, $craftInventoryItemCell);

        $craftInventoryItemCell->forceFill([
            'file_original_name' => $file->getClientOriginalName(),
        ])->save();

        $this->redirector->back();
    }

    public function getDownloadCellValueUpload(
        CraftInventoryItemCell $craftInventoryItemCell
    ): StreamedResponse {
        $storedName = $this->storedFileName($craftInventoryItemCell);

        return Storage::download(
            'uploads/inventar/' . $storedName,
            $craftInventoryItemCell->file_original_name ?: $storedName
        );
    }

    public function removeUploadedFile(CraftInventoryItemCell $craftInventoryItemCell): RedirectResponse
    {
        Storage::delete('uploads/inventar/' . $this->storedFileName($craftInventoryItemCell));

        $craftInventoryItemCell->forceFill(['file_original_name' => null])->save();
        $this->craftInventoryItemCellService->updateCellValue('', $craftInventoryItemCell);

        return $this->redirector->back();
    }

    /**
     * cell_value doubles as the on-disk filename. Legacy rows hold whatever the
     * client sent, so anything that is not a plain filename is refused rather
     * than interpolated into a storage path.
     */
    private function storedFileName(CraftInventoryItemCell $craftInventoryItemCell): string
    {
        $storedName = (string) $craftInventoryItemCell->cell_value;

        abort_if($storedName === '' || basename($storedName) !== $storedName, 404);

        return $storedName;
    }
}
