<table>
    <thead>
    <tr>
        <th style="background-color: #CECDD8;"></th>
        @foreach($data['budgetTable']->columns as $column)
            <th style="background-color: #CECDD8;">{{$column->name}}</th>
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
    @foreach($data['budgetTypeCost'] as $mainPosition)
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;"
                colspan="{{ $columnCount }}">
                {{$mainPosition->name}}
            </td>
        </tr>
        @foreach($mainPosition->subPositions as $subPosition)
            <tr>
                <td style="background-color: #CECDD8;"
                    colspan="{{ $columnCount }}">
                    {{$subPosition->name}}
                </td>
            </tr>
            @foreach($subPosition->subPositionRows as $subPositionRow)
                <tr>
                    <td></td>
                    @foreach($subPositionRow->cells as $columnCell)
                        <td>{{$columnCell->value}}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td style="background-color: #CECDD8;" colspan="3"></td>
                <td style="background-color: #CECDD8;" align="right">SUM</td>
                @foreach($data['budgetTable']->columns->skip(3) as $column)
                    <td style="background-color: #CECDD8;">
                        {{
                            isset($subPosition->columnSums[$column->id]) ?
                                $subPosition->columnSums[$column->id]['sum']
                                : ''
                        }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;" colspan="3"></td>
            <td style="color:#FFFFFF; background-color: #27233C;" align="right">SUM</td>
            @foreach($data['budgetTable']->columns->skip(3) as $column)
                <td style="color:#FFFFFF; background-color: #27233C;">
                    {{
                        isset($mainPosition->columnSums[$column->id]) ?
                            $mainPosition->columnSums[$column->id]['sum'] :
                            ''
                    }}
                </td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="3"></td>
        <td align="right">SUM</td>
        @foreach($data['budgetTable']->columns->skip(3) as $column)
            <td>
                {{ $data['budgetTable']->costSums[$column->id] ?? '' }}
            </td>
        @endforeach
    </tr>
    <tr>
        <td colspan="3"></td>
        <td align="right">SUM ausgeklammerte Posten</td>
        @foreach($data['budgetTable']->commentedCostSums as $commentedCostSum)
            <td>{{$commentedCostSum}}</td>
        @endforeach
    </tr>
    {{-- Budget Type Earning --}}
    <tr>
        <td rowspan="2" colspan="{{ $columnCount }}">Einnahmen</td>
    </tr>
    <tr>
        <td colspan="{{ $columnCount }}"></td>
    </tr>
    @foreach($data['budgetTypeEarning'] as $mainPosition)
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;"
                colspan="{{ $columnCount }}">
                {{$mainPosition->name}}
            </td>
        </tr>
        @foreach($mainPosition->subPositions as $subPosition)
            <tr>
                <td style="background-color: #CECDD8;"
                    colspan="{{ $columnCount }}">
                    {{$subPosition->name}}
                </td>
            </tr>
            @foreach($subPosition->subPositionRows as $subPositionRow)
                <tr>
                    <td></td>
                    @foreach($subPositionRow->cells as $columnCell)
                        <td>{{$columnCell->value}}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td style="background-color: #CECDD8;" colspan="3"></td>
                <td style="background-color: #CECDD8;" align="right">SUM</td>
                @foreach($data['budgetTable']->columns->skip(3) as $column)
                    <td style="background-color: #CECDD8;">
                        {{
                            isset($subPosition->columnSums[$column->id]) ?
                                $subPosition->columnSums[$column->id]['sum'] :
                                ''
                        }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td style="color:#FFFFFF; background-color: #27233C;" colspan="3"></td>
            <td style="color:#FFFFFF; background-color: #27233C;" align="right">SUM</td>
            @foreach($data['budgetTable']->columns->skip(3) as $column)
                <td style="color:#FFFFFF; background-color: #27233C;">
                    {{
                        isset($mainPosition->columnSums[$column->id]) ?
                            $mainPosition->columnSums[$column->id]['sum'] :
                            ''
                    }}
                </td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="3"></td>
        <td align="right">SUM</td>
        @foreach($data['budgetTable']->columns->skip(3) as $column)
            <td>
                {{ $data['budgetTable']->earningSums[$column->id] ?? '' }}
            </td>
        @endforeach
    </tr>
    <tr>
        <td colspan="3"></td>
        <td align="right">SUM ausgeklammerte Posten</td>
        @foreach($data['budgetTable']->commentedEarningSums as $commentedEarningSum)
            <td>{{$commentedEarningSum}}</td>
        @endforeach
    </tr>
    {{-- Earnings minus costs --}}
    <tr>
        <td colspan="{{ $columnCount }}"></td>
    </tr>
    <tr>
        <td colspan="3" align="center">Einnahmen - Ausgaben</td>
        <td align="right">SUM</td>
        @foreach($data['budgetTable']->columns->skip(3) as $column)
            <td>
                {{ $data['allOverSums'][$column->id] ?? '' }}
            </td>
        @endforeach
    </tr>
    </tbody>
</table>
