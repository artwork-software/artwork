<?php

namespace Artwork\Modules\Budget\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Http\Requests\UpdateTableColumnOrderRequest;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\TableColumnOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Throwable;

class TableColumnOrderController extends Controller
{
    public function __construct(
        private readonly Redirector $redirector,
        private readonly TableColumnOrderService $tableColumnOrderService,
    ) {
    }

    /**
     * Globale Sage-Einstellung: Reihenfolge der ersten beiden Spalten (KTO/KST)
     * wird systemweit auf alle Budgettabellen angewendet.
     *
     * @throws Throwable
     */
    public function updateTableColumnOrders(
        UpdateTableColumnOrderRequest $updateTableColumnOrderRequest,
    ): RedirectResponse {
        $columnOrders = $updateTableColumnOrderRequest->get('tableColumnOrders');

        $this->tableColumnOrderService->updateTableColumnOrders($columnOrders);

        // Nur die ersten beiden Spalten jeder Tabelle sind betroffen - gezielt und
        // schlank laden statt alle Tabellen samt kompletten Spalten, und nur echte
        // Positionsänderungen schreiben.
        $affectedTableIds = [];

        Column::query()
            ->select(['id', 'table_id', 'position'])
            ->orderBy('id')
            ->get()
            ->groupBy('table_id')
            ->each(function (Collection $columns) use ($columnOrders, &$affectedTableIds): void {
                if ($columns->count() < 2) {
                    return;
                }

                $firstTwoColumns = $columns->take(2)->values()->all();
                foreach ($columnOrders as $index => $columnOrder) {
                    $column = $firstTwoColumns[$columnOrder - 1] ?? null;
                    if ($column && (int) $column->position !== $index) {
                        Column::query()->whereKey($column->id)->update(['position' => $index]);
                        $affectedTableIds[$column->table_id] = true;
                    }
                }
            });

        // Bulk-Updates feuern keine Model-Events - Budget-Cache der betroffenen
        // Projekte explizit invalidieren.
        if ($affectedTableIds !== []) {
            Table::query()
                ->whereIn('id', array_keys($affectedTableIds))
                ->whereNotNull('project_id')
                ->pluck('project_id')
                ->unique()
                ->each(static fn(int $projectId) => BudgetUpdated::dispatch($projectId));
        }

        return $this->redirector->back();
    }
}
