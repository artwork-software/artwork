<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\CreateCraftInventoryCategoryRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryCategoryController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryCategoryService $craftsInventoryCategoryService
    ) {
    }

    public function create(CreateCraftInventoryCategoryRequest $request): RedirectResponse
    {
        try {
            $this->craftsInventoryCategoryService->create(
                $request->integer('craftId'),
                $request->string('name')
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not create crafts inventory category for reason: "%s"',
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', 'Kategorie konnte nicht gespeichert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function updateName(
        CraftInventoryCategory $craftInventoryCategory,
        UpdateCraftInventoryCategoryNameRequest $request
    ): RedirectResponse {
        $name = $request->get('name');

        try {
            $this->craftsInventoryCategoryService->updateName($name, $craftInventoryCategory);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory category name to: "%s" for reason: "%s"',
                    $name,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', 'Kategoriename konnte nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function updateOrder(
        CraftInventoryCategory $craftInventoryCategory,
        UpdateCraftInventoryCategoryOrderRequest $request
    ) {
        $order = $request->integer('order');

        try {
            $this->craftsInventoryCategoryService->updateOrder($craftInventoryCategory, $order);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory category order to: "%s" for reason: "%s"',
                    $order,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', 'Kategorieposition konnte nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function forceDelete(CraftInventoryCategory $craftInventoryCategory): RedirectResponse
    {
        if (!$this->craftsInventoryCategoryService->forceDelete($craftInventoryCategory)) {
            return $this->redirector
                ->back()
                ->with('error', 'Kategorie konnte nicht gelöscht werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
}
