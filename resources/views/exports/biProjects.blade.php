<table>
    <thead>
        <tr>
            @foreach($columns as $column)
                <th style="background-color: #cccccc; font-weight: bold;">{{ __($column) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($columns as $column)
                    <td>{{ $row[$column] ?? '' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
