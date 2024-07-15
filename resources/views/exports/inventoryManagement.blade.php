@php $columnCount = $columns->count(); @endphp
<table>
    <thead>
    <tr>
        @foreach($columns as $column)
            <th style="background-color: #CECDD8;">{{ $column->name }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($crafts as $craft)
    <tr>
        <td style="font-weight: bold;" colspan="{{ $columnCount }}">{{ $craft['craftName'] }}</td>
    </tr>
        @foreach($craft['filteredInventoryCategories'] as $category)
            <tr>
                <td style="color:#FFFFFF; background-color: #27233C; font-weight: bold;" colspan="{{ $columnCount }}">{{ $category['name'] }}</td>
            </tr>
            @foreach($category['groups'] as $group)
                <tr>
                    <td style="background-color: #CECDD8; font-weight: bold;" colspan="{{ $columnCount }}">{{ $group['name'] }}</td>
                </tr>
                @foreach($group['items'] as $item)
                    <tr>
                        @foreach($item['cells'] as $cell)
                            <td>
                            @if($cell['column']['type'] === \Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum::DATE->value)
                                {{ \Carbon\Carbon::parse($cell['cell_value'])->format('d.m.y') }}
                            @elseif($cell['column']['type'] === \Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum::CHECKBOX->value)
                                {{ $cell['cell_value'] == 'true' ? 'Ja' : 'Nein' }}
                            @else
                                {{ $cell['cell_value'] }}
                            @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            <tr><td colspan="{{ $columnCount }}"></td></tr>
        @endforeach
    @endforeach
    </tbody>
</table>
