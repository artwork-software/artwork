@inject("formulaService", "App\Support\Services\ProjectBudgetExportFormulaService")
<table>
    <thead>
    <tr>
        <th style="background-color: #CECDD8;"></th>
        @foreach($data['budgetTable']->columns as $column)
            <th style="background-color: #CECDD8;">{{ $column->name }}</th>
        @endforeach
    </tr>
    </thead>
    @php $columnCount = $data['budgetTable']->columns->count() + 1; @endphp
    <tbody>
    {{-- Budget Type Cost --}}
    <tr>
        <td rowspan="2" colspan="{{ $columnCount }}">Ausgaben</td>
    </tr>
    <tr>
        <td colspan="{{ $columnCount }}"></td>
    </tr>
    @php
        $currentRowCount = 3;
        $mainPositionSumCellsCollection = [];
        $budgetTypeCostDeterminedCellsCollection = [];
    @endphp
    @foreach($data['budgetTypeCost'] as $mainPosition)
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;"
                colspan="{{ $columnCount }}">
                {{ $mainPosition->name }}
            </td>
        </tr>
        @php
            $currentRowCount++;
            $subPositionSumCellsCollection = [];
        @endphp
        @foreach($mainPosition->subPositions as $subPosition)
            @php $subPositionRowSumCellsCollection = []; @endphp
            <tr>
                <td style="background-color: #CECDD8;"
                    colspan="{{ $columnCount }}">
                    {{ $subPosition->name }}
                </td>
            </tr>
            @php $currentRowCount++; @endphp
            @foreach($subPosition->subPositionRows as $subPositionRow)
                <tr>
                    <td></td>
                    @foreach($subPositionRow->cells as $columnCell)
                        <td>
                            @php
                                $subPositionRowSumCellsCollection[$columnCell->column->id][] = $formulaService
                                    ->determineExcelColumn(
                                        $data['budgetTable']->columns,
                                        $columnCell->column->id,
                                        $currentRowCount
                                    );
                            @endphp
                            @if($columnCell->column->type === "empty")
                                {{ $columnCell->value }}
                            @elseif($columnCell->column->type === "sage")
                                {{ $columnCell->sage_value ?: 0 }}
                            @else
                                {{
                                    $formulaService->createFormula(
                                        $data['budgetTable']->columns,
                                        $currentRowCount,
                                        $columnCell->column->linked_first_column,
                                        $columnCell->column->type === "sum" ? "+" : "-",
                                        $columnCell->column->linked_second_column
                                    )
                                }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                @php $currentRowCount++; @endphp
            @endforeach
            <tr>
                <td style="background-color: #CECDD8;" colspan="3"></td>
                <td style="background-color: #CECDD8;" align="right">SUM</td>
                @foreach($subPositionRowSumCellsCollection as $columnId => $subPositionRowSumCells)
                    @continue($loop->index <= 2)
                    @php
                        $subPositionSumCellsCollection[$columnId][] = $formulaService->determineExcelColumn(
                              $data['budgetTable']->columns,
                              $columnId,
                              $currentRowCount,
                        );
                    @endphp
                    <td style="background-color: #CECDD8;">
                        {{ $formulaService->createColumnSumRangeFormula($subPositionRowSumCells) }}
                    </td>
                @endforeach
            </tr>
            @php $currentRowCount++; @endphp
        @endforeach
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;" colspan="3"></td>
            <td style="color:#FFFFFF; background-color: #27233C;" align="right">SUM</td>
            @foreach($subPositionSumCellsCollection as $columnId => $subPositionSumCells)
                @php
                    $mainPositionSumCellsCollection[$columnId][] = $formulaService->determineExcelColumn(
                          $data['budgetTable']->columns,
                          $columnId,
                          $currentRowCount,
                    );
                @endphp
                <td style="color:#FFFFFF; background-color: #27233C;">
                    {{ $formulaService->createColumnSumSeparatedFormula($subPositionSumCells) }}
                </td>
            @endforeach
        </tr>
        @php $currentRowCount++; @endphp
    @endforeach
    <tr>
        <td colspan="3"></td>
        <td align="right">SUM</td>
        @foreach($mainPositionSumCellsCollection as $columnId => $mainPositionSumCells)
            @php
                $budgetTypeCostDeterminedCellsCollection[$columnId] = $formulaService->determineExcelColumn(
                      $data['budgetTable']->columns,
                      $columnId,
                      $currentRowCount,
                );
            @endphp
            <td>
                {{ $formulaService->createColumnSumSeparatedFormula($mainPositionSumCells) }}
            </td>
        @endforeach
    </tr>
    @php $currentRowCount++; @endphp
    {{-- Budget Type Earning --}}
    <tr>
        <td rowspan="2" colspan="{{ $columnCount }}">Einnahmen</td>
    </tr>
    @php $currentRowCount++; @endphp
    <tr>
        <td colspan="{{ $columnCount }}"></td>
    </tr>
    @php
        $currentRowCount++;
        $mainPositionSumCellsCollection = [];
        $budgetTypeEarningDeterminedCellsCollection = [];
    @endphp
    @foreach($data['budgetTypeEarning'] as $mainPosition)
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;"
                colspan="{{ $columnCount }}">
                {{ $mainPosition->name }}
            </td>
        </tr>
        @php
            $currentRowCount++;
            $subPositionSumCellsCollection = [];
        @endphp
        @foreach($mainPosition->subPositions as $subPosition)
            @php $subPositionRowSumCellsCollection = []; @endphp
            <tr>
                <td style="background-color: #CECDD8;"
                    colspan="{{ $columnCount }}">
                    {{ $subPosition->name }}
                </td>
            </tr>
            @php $currentRowCount++; @endphp
            @foreach($subPosition->subPositionRows as $subPositionRow)
                <tr>
                    <td></td>
                    @foreach($subPositionRow->cells as $columnCell)
                        <td>
                            @php
                                $subPositionRowSumCellsCollection[$columnCell->column->id][] = $formulaService
                                    ->determineExcelColumn(
                                        $data['budgetTable']->columns,
                                        $columnCell->column->id,
                                        $currentRowCount
                                    );
                            @endphp
                            @if($columnCell->column->type === "empty")
                                {{ $columnCell->value }}
                            @elseif($columnCell->column->type === "sage")
                                {{ $columnCell->sage_value ?: 0 }}
                            @else
                                {{
                                    $formulaService->createFormula(
                                        $data['budgetTable']->columns,
                                        $currentRowCount,
                                        $columnCell->column->linked_first_column,
                                        $columnCell->column->type === "sum" ? "+" : "-",
                                        $columnCell->column->linked_second_column
                                    )
                                }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                @php $currentRowCount++; @endphp
            @endforeach
            <tr>
                <td style="background-color: #CECDD8;" colspan="3"></td>
                <td style="background-color: #CECDD8;" align="right">SUM</td>
                @foreach($subPositionRowSumCellsCollection as $columnId => $subPositionRowSumCells)
                    @continue($loop->index <= 2)
                    @php
                        $subPositionSumCellsCollection[$columnId][] = $formulaService->determineExcelColumn(
                              $data['budgetTable']->columns,
                              $columnId,
                              $currentRowCount,
                        );
                    @endphp
                    <td style="background-color: #CECDD8;">
                        {{ $formulaService->createColumnSumRangeFormula($subPositionRowSumCells) }}
                    </td>
                @endforeach
            </tr>
            @php $currentRowCount++; @endphp
        @endforeach
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;" colspan="3"></td>
            <td style="color:#FFFFFF; background-color: #27233C;" align="right">SUM</td>
            @foreach($subPositionSumCellsCollection as $columnId => $subPositionSumCells)
                @php
                    $mainPositionSumCellsCollection[$columnId][] = $formulaService->determineExcelColumn(
                          $data['budgetTable']->columns,
                          $columnId,
                          $currentRowCount,
                    );
                @endphp
                <td style="color:#FFFFFF; background-color: #27233C;">
                    {{ $formulaService->createColumnSumSeparatedFormula($subPositionSumCells) }}
                </td>
            @endforeach
        </tr>
        @php $currentRowCount++; @endphp
    @endforeach
    <tr>
        <td colspan="3"></td>
        <td align="right">SUM</td>
        @foreach($mainPositionSumCellsCollection as $columnId => $mainPositionSumCells)
            @php
                $budgetTypeEarningDeterminedCellsCollection[$columnId] = $formulaService->determineExcelColumn(
                      $data['budgetTable']->columns,
                      $columnId,
                      $currentRowCount,
                );
            @endphp
            <td>
                {{ $formulaService->createColumnSumSeparatedFormula($mainPositionSumCells) }}
            </td>
        @endforeach
    </tr>
    @php $currentRowCount++; @endphp
    {{-- Earnings minus costs --}}
    <tr>
        <td colspan="{{ $columnCount }}"></td>
    </tr>
    <tr>
        <td colspan="3" align="center">Einnahmen - Ausgaben</td>
        <td align="right">SUM</td>
        @foreach($budgetTypeCostDeterminedCellsCollection as $columnId => $budgetTypeCostDeterminedCell)
            <td>
                {{
                    $formulaService->createSubtractionFormula(
                        $budgetTypeEarningDeterminedCellsCollection[$columnId],
                        $budgetTypeCostDeterminedCell
                    )
                }}
            </td>
        @endforeach
    </tr>
    @php $currentRowCount++; @endphp
    </tbody>
</table>
