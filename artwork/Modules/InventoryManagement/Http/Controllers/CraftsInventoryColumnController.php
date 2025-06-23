<?php

namespace Artwork\Modules\InventoryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\CreateCraftsInventoryColumnRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\DuplicateCraftsInventoryColumnRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnBackgroundColorRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnTypeOptionsRequest;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftsInventoryColumnController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService,
        private readonly Translator $translator
    ) {
    }

    public function create(CreateCraftsInventoryColumnRequest $request): RedirectResponse
    {
        try {
            $this->craftsInventoryColumnService->create(
                $request->string('name'),
                $request->enum('type.id', CraftsInventoryColumnTypeEnum::class),
                $request->string('defaultOption', ''),
                $request->get('typeOptions', []),
                ''
            );
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not create crafts inventory column for reason: "%s"',
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.column.errors.create')
                );
        }

        return $this->redirector->back();
    }

    public function duplicate(DuplicateCraftsInventoryColumnRequest $request): RedirectResponse
    {
        $columnId = $request->integer('columnId');

        try {
            $this->craftsInventoryColumnService->duplicate($columnId);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not duplicate crafts inventory column with id "%d" for reason: "%s"',
                    $columnId,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.column.errors.duplicate')
                );
        }

        return $this->redirector->back();
    }

    public function updateName(
        CraftsInventoryColumn $craftsInventoryColumn,
        UpdateCraftsInventoryColumnNameRequest $request
    ): RedirectResponse {
        $name = $request->string('name');

        try {
            $this->craftsInventoryColumnService->updateName($name, $craftsInventoryColumn);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory column name to: "%s" for reason: "%s"',
                    $name,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.column.errors.updateName')
                );
        }

        return $this->redirector->back();
    }

    public function updateBackgroundColor(
        CraftsInventoryColumn $craftsInventoryColumn,
        UpdateCraftsInventoryColumnBackgroundColorRequest $request
    ): RedirectResponse {
        $backgroundColor = $request->string('background_color');

        try {
            $this->craftsInventoryColumnService->updateBackgroundColor($backgroundColor, $craftsInventoryColumn);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory column background color to: "%s" for reason: "%s"',
                    $backgroundColor,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.column.errors.updateBackgroundColor')
                );
        }

        return $this->redirector->back();
    }

    public function updateTypeOptions(
        CraftsInventoryColumn $craftsInventoryColumn,
        UpdateCraftsInventoryColumnTypeOptionsRequest $request
    ): RedirectResponse {
        $selectOptions = $request->get('selectOptions');

        try {
            $this->craftsInventoryColumnService->updateTypeOptions($selectOptions, $craftsInventoryColumn);
        } catch (Throwable $t) {
            $this->logger->error(
                sprintf(
                    'Could not update crafts inventory column type options to: "%s" for reason: "%s"',
                    implode(',', $selectOptions),
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.column.errors.updateTypeOptions')
                );
        }

        return $this->redirector->back();
    }

    public function forceDelete(CraftsInventoryColumn $craftsInventoryColumn): RedirectResponse
    {
        if (!$this->craftsInventoryColumnService->forceDelete($craftsInventoryColumn)) {
            return $this->redirector
                ->back()
                ->with(
                    'error',
                    $this->translator->get('flash-messages.inventory-management.column.errors.delete')
                );
        }

        return $this->redirector->back();
    }

    public function reorderColumns(Request $request): void
    {
        $request->validate(['columns.*' => 'integer|exists:crafts_inventory_columns,id']);
        $this->craftsInventoryColumnService->reorderColumns($request->collect('columns'));
    }
}
