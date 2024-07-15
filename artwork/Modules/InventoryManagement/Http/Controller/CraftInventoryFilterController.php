<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Requests\Filter\UpdateOrCreateInventoryFilterRequest;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryFilterController
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterService,
        private readonly Translator $translator
    ) {
    }
    public function updateOrCreate(
        UpdateOrCreateInventoryFilterRequest $request,
        AuthManager $authManager
    ): RedirectResponse {
        /** @var Collection $filter */
        $filter = $request->collect('filter')->map(fn(array $filter) => $filter['craftId']);
        try {
            $this->inventoryManagementUserFilterService->updateOrCreate(
                $authManager->id(),
                $filter
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update inventory management user filter to: "%s" for reason: "%s"',
                    implode(',', $filter->all()),
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.filter.errors.updateOrCreate')
                );
        }

        return $this->redirector->back();
    }
}
