<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Filter\UpdateOrCreateInventoryFilterRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryFilterController
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterService
    ) {
    }
    public function updateOrCreate(
        UpdateOrCreateInventoryFilterRequest $request,
        AuthManager $authManager
    ): RedirectResponse {
        $filter = $request->collect('filter');
        try {
            $this->inventoryManagementUserFilterService->updateOrCreate(
                $authManager->id(),
                $request->collect('filter')
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update inventory management user filter to: "%s" for reason: "%s"',
                    implode(',', $filter->toArray()),
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', 'Filtereinstellungen konnten nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
}
