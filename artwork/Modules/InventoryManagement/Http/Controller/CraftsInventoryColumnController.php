<?php

namespace Artwork\Modules\InventoryManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\CreateCraftsInventoryColumnRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\DuplicateCraftsInventoryColumnRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnBackgroundColorRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnTypeOptionsRequest;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Psr\Log\LoggerInterface;
use Throwable;

class CraftsInventoryColumnController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Redirector $redirector,
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService
    ) {
    }

    public function create(CreateCraftsInventoryColumnRequest $request): RedirectResponse
    {
        try {
            $this->craftsInventoryColumnService->create(
                $request->string('name'),
                $request->enum('type.id', CraftsInventoryColumnTypeEnum::class),
                $request->get('typeOptions'),
                '',
                $request->get('defaultOption')
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
                ->with('error', 'Spalte konnte nicht gespeichert werden. Bitte versuche es erneut.');
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
                ->with('error', 'Spalte konnte nicht dupliziert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function updateName(
        CraftsInventoryColumn $craftsInventoryColumn,
        UpdateCraftsInventoryColumnNameRequest $request
    ): RedirectResponse {
        $name = $request->get('name');

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
                ->with('error', 'Spaltenname konnte nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function updateBackgroundColor(
        CraftsInventoryColumn $craftsInventoryColumn,
        UpdateCraftsInventoryColumnBackgroundColorRequest $request
    ): RedirectResponse {
        $backgroundColor = $request->get('background_color');

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
                ->with('error', 'Spaltenfarbe konnte nicht aktualisiert werden. Bitte versuche es erneut.');
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
                    $selectOptions,
                    $t->getMessage()
                )
            );

            return $this->redirector
                ->back()
                ->with('error', 'Spalten-Auswahloptionen konnte nicht aktualisiert werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }

    public function forceDelete(CraftsInventoryColumn $craftsInventoryColumn): RedirectResponse
    {
        if (!$this->craftsInventoryColumnService->forceDelete($craftsInventoryColumn)) {
            return $this->redirector
                ->back()
                ->with('error', 'Spalte konnte nicht gelöscht werden. Bitte versuche es erneut.');
        }

        return $this->redirector->back();
    }
}
