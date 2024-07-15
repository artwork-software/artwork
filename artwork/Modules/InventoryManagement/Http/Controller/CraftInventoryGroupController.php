<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\CreateCraftInventoryGroupRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryGroupController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryGroupService $craftInventoryGroupService,
        private readonly Translator $translator
    ) {
    }

    public function create(CreateCraftInventoryGroupRequest $request): RedirectResponse
    {
        try {
            $this->craftInventoryGroupService->create(
                $request->integer('categoryId'),
                $request->string('name'),
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not create crafts inventory group for reason: "%s"',
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', $this->translator->get('flash-messages.inventory-management.group.errors.create'));
        }

        return $this->redirector->back();
    }

    public function updateName(
        CraftInventoryGroup $craftInventoryGroup,
        UpdateCraftInventoryGroupNameRequest $request
    ): RedirectResponse {
        $name = $request->string('name');

        try {
            $this->craftInventoryGroupService->updateName($name, $craftInventoryGroup);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory group name to: "%s" for reason: "%s"',
                    $name,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.group.errors.updateName')
                );
        }

        return $this->redirector->back();
    }

    public function updateOrder(
        CraftInventoryGroup $craftInventoryGroup,
        UpdateCraftInventoryGroupOrderRequest $request
    ) {
        $order = $request->integer('order');

        try {
            $this->craftInventoryGroupService->updateOrder($craftInventoryGroup, $order);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory group order to: "%s" for reason: "%s"',
                    $order,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.group.errors.updateOrder')
                );
        }

        return $this->redirector->back();
    }

    public function forceDelete(CraftInventoryGroup $craftInventoryGroup): RedirectResponse
    {
        if (!$this->craftInventoryGroupService->forceDelete($craftInventoryGroup)) {
            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.group.errors.delete')
                );
        }

        return $this->redirector->back();
    }
}
