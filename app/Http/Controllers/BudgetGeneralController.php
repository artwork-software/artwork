<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
use Artwork\Modules\Budget\Http\Requests\UpdateBudgetColumnSettingRequest;
use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Artwork\Modules\Budget\Services\BudgetColumnSettingService;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Throwable;

class BudgetGeneralController extends Controller
{
    public function __construct(
        private readonly BudgetColumnSettingService $budgetColumnSettingService,
        private readonly Sage100Service $sage100Service
    ) {
        $this->authorizeResource(BudgetColumnSetting::class, 'budgetColumnSetting');
    }

    public function index(): Response
    {
        return Inertia::render(
            'BudgetSettingsGeneral/Index',
            [
                'budgetColumnSettings' => $this->budgetColumnSettingService->getAll()
            ]
        );
    }

    public function update(
        UpdateBudgetColumnSettingRequest $updateBudgetColumnSettingRequest,
        BudgetColumnSetting $budgetColumnSetting
    ): RedirectResponse {
        try {
            $this->budgetColumnSettingService->updateFromRequest(
                $budgetColumnSetting,
                $updateBudgetColumnSettingRequest
            );
        } catch (Throwable $t) {
            return Redirect::back()->with('error', __('flash-messages.budget-general-setting.error.update'));
        }

        return Redirect::back()->with('success', __('flash-messages.budget-general-setting.success.update'));
    }

    public function moveSageDataRow(ColumnCell $columnCell, ColumnCell $movedColumn, Request $request): RedirectResponse
    {
        $this->validateSageRowMove($request);

        // Redirect samt Fehlermeldung weiterreichen, sonst verschwindet die Ablehnung des Drops
        return $this->sage100Service->moveSageDataRow($columnCell, $movedColumn, $request);
    }

    public function moveSageDataRowToNewRow(
        Request $request,
        Table $table_id,
        SubPosition $sub_position_id,
        int $positionBefore,
        ColumnCell $columnCell
    ): void {
        $this->validateSageRowMove($request);

        // Neue Zeile nur in einer Unterposition der angegebenen Tabelle anlegen
        abort_unless((int) $sub_position_id->mainPosition?->table_id === (int) $table_id->id, 404);

        // Ohne zu verschiebende Buchung bliebe sonst eine leere Zeile samt verschobener Positionen zurück
        $movableData = $request->multiple === false
            ? $columnCell->sageAssignedData
            : $columnCell->sageAssignedData->whereIn('id', $request->input('selectedData', []));
        abort_if($movableData->isEmpty(), 422);

        if ($request->multiple === false) {
            $this->sage100Service->moveSingleSageDataRowToNewRow(
                $request,
                $table_id,
                $sub_position_id,
                $positionBefore,
                $columnCell
            );
        } else {
            $this->sage100Service->moveMultipleSageDataRowToNewRow(
                $request,
                $table_id,
                $sub_position_id,
                $positionBefore,
                $columnCell
            );
        }
    }

    /**
     * Mehrfachauswahl braucht die Liste der gewählten Buchungen, sonst scheitert der Service mit einem 500er.
     */
    private function validateSageRowMove(Request $request): void
    {
        $request->validate([
            'multiple' => ['required', 'boolean'],
            'selectedData' => ['required_if_accepted:multiple', 'array'],
            'selectedData.*' => ['integer'],
        ]);
    }

    public function updateColumnRelevant(Column $column, ColumnRelevanceService $columnRelevanceService): void
    {
        // Nur echte Wertspalten dürfen budgetrelevant sein; das Flag kann nur
        // verschoben werden (eine Tabelle darf nie ohne budgetrelevante Spalte sein).
        abort_unless($columnRelevanceService->isFlaggable($column), 422);

        $columnRelevanceService->assignExclusive($column);
    }

    public function getTrashed(): Response|ResponseFactory
    {
        $selectedCell = request('selectedCell')
            ? ColumnCell::withTrashed()->find(request('selectedCell'))
            : null;

        $selectedRow = request('selectedRow')
            ? SubPositionRow::withTrashed()->find(request('selectedRow'))
            : null;

        $templates = null;
        if (request('useTemplates')) {
            $templates = Table::where('is_template', true)->get();
        }

        $withTrashed = fn ($q) => $q->withTrashed();
        $withTrashedPos = fn ($q) => $q->withTrashed()->orderBy('position');
        $withTrashedCreatedDesc = fn ($q) => $q->withTrashed()->orderBy('created_at', 'desc');

        $tables = Table::query()
            ->where('is_template', true)
            ->onlyTrashed()
            ->with([
                // WICHTIG: überall wo die Models SoftDeletes haben -> withTrashed()
                'columns' => $withTrashed,

                'mainPositions' => $withTrashedPos,
                'mainPositions.verified' => $withTrashed,

                'mainPositions.subPositions' => $withTrashedPos,
                'mainPositions.subPositions.verified' => $withTrashed,

                'mainPositions.subPositions.subPositionRows' => $withTrashedPos,

                // je nachdem ob Cells/Columns auch softdeleted sind:
                'mainPositions.subPositions.subPositionRows.cells' => $withTrashed,
                'mainPositions.subPositions.subPositionRows.cells.column' => $withTrashed,
            ])
            ->get();

        return Inertia::render('BudgetSettingsTemplates/TrashIndex', [
            'budget' => [
                'table' => $tables,
                'selectedCell' => $selectedCell?->load([
                    'calculations', // falls calculations softdeleted sind: 'calculations' => $withTrashed
                    'comments.user',
                    'comments' => $withTrashedCreatedDesc, // falls comments SoftDeletes haben
                ]),
                'selectedRow' => $selectedRow?->load([
                    'comments.user',
                    'comments' => $withTrashedCreatedDesc, // falls comments SoftDeletes haben
                ]),
                'templates' => $templates,
            ],
        ]);
    }
}
