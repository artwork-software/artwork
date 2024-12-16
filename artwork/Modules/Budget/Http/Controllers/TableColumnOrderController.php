<?php

namespace Artwork\Modules\Budget\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Budget\Http\Requests\UpdateTableColumnOrderRequest;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\TableColumnOrderService;
use Artwork\Modules\Budget\Services\TableService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Throwable;

class TableColumnOrderController extends Controller
{
    public function __construct(
        private readonly Redirector $redirector,
        private readonly TableColumnOrderService $tableColumnOrderService,
        private readonly TableService $tableService,
        private readonly ColumnService $columnService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function updateTableColumnOrders(
        UpdateTableColumnOrderRequest $updateTableColumnOrderRequest,
    ): RedirectResponse {
        $columnOrders = $updateTableColumnOrderRequest->get('tableColumnOrders');

        $this->tableColumnOrderService->updateTableColumnOrders($columnOrders);

        /** @var Table $table */
        foreach ($this->tableService->getAll(['columns']) as $table) {
            /** @var Collection $columns */
            $columns = $table->getRelation('columns');
            if (count($columns) >= 2) {
                $firstTwoColumns = $columns->take(2)->all();
                foreach ($columnOrders as $index => $columnOrder) {
                    $this->columnService->updateOrFail($firstTwoColumns[$columnOrder - 1], ['position' => $index]);
                }
            }
        }

        return $this->redirector->back();
    }
}
