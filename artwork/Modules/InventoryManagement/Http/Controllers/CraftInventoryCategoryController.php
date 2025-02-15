<?php

namespace Artwork\Modules\InventoryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\CreateCraftInventoryCategoryRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryCategoryController extends Controller
{
    public function __construct(
        private readonly Translator $translator,
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryCategoryService $craftInventoryCategoryService
    ) {
    }

    public function create(CreateCraftInventoryCategoryRequest $request): RedirectResponse
    {
        try {
            $this->craftInventoryCategoryService->create(
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
                ->with('error', $this->translator->get('flash-messages.inventory-management.category.errors.create'));
        }

        return $this->redirector->back();
    }

    public function updateName(
        CraftInventoryCategory $craftInventoryCategory,
        UpdateCraftInventoryCategoryNameRequest $request
    ): RedirectResponse {
        $name = $request->string('name');

        try {
            $this->craftInventoryCategoryService->updateName($name, $craftInventoryCategory);
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
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.category.errors.updateName')
                );
        }

        return $this->redirector->back();
    }

    public function updateOrder(
        CraftInventoryCategory $craftInventoryCategory,
        UpdateCraftInventoryCategoryOrderRequest $request
    ) {
        $order = $request->integer('order');

        try {
            $this->craftInventoryCategoryService->updateOrder($craftInventoryCategory, $order);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory category order to: "%d" for reason: "%s"',
                    $order,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.category.errors.updateOrder')
                );
        }

        return $this->redirector->back();
    }

    public function forceDelete(CraftInventoryCategory $craftInventoryCategory): RedirectResponse
    {
        if (!$this->craftInventoryCategoryService->forceDelete($craftInventoryCategory)) {
            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.category.errors.delete')
                );
        }

        return $this->redirector->back();
    }
}
