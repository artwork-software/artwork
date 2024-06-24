<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\CreateCraftInventoryGroupRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftInventoryGroupController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftInventoryGroupService $craftInventoryGroupService
    ) {
    }

    public function create(CreateCraftInventoryGroupRequest $request): RedirectResponse
    {
        try {
            $this->craftInventoryGroupService->create(
                $request->integer('categoryId'),
                $request->string('name'),
                $request->integer('order'),
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
                ->with('error', 'Gruppe konnte nicht gespeichert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function updateName(
        CraftInventoryGroup $craftInventoryGroup,
        UpdateCraftInventoryGroupNameRequest $request
    ): RedirectResponse {
        $name = $request->get('name');

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
                ->with('error', 'Gruppenname konnte nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
//
//    public function updateOrder(
//        CraftInventoryCategory $craftInventoryCategory,
//        UpdateCraftInventoryGroupOrderRequest $request
//    ) {
//        $order = $request->integer('order');
//
//        try {
//            $this->craftsInventoryCategoryService->updateOrder($craftInventoryCategory, $order);
//        } catch (Throwable $t) {
//            $this->logger->error(
//                sprintf(
//                    'Could not update crafts inventory category order to: "%s" for reason: "%s"',
//                    $order,
//                    $t->getMessage()
//                )
//            );
//
//            return $this->redirector
//                ->back()
//                ->with('error', 'Kategorieposition konnte nicht aktualisiert werden. Bitte versuche es erneut.');
//        }
//
//        return $this->redirector->back();
//    }
//
    public function forceDelete(CraftInventoryGroup $craftInventoryGroup): RedirectResponse
    {
        if (!$this->craftInventoryGroupService->forceDelete($craftInventoryGroup)) {
            return $this->redirector
                ->back()
                ->with('error', 'Gruppe konnte nicht gelöscht werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
}
