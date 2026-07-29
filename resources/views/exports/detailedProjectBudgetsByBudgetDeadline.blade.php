<table>
    <thead>
    <tr>
        @foreach($columns as $label)
            <th>{{ $label }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            @foreach($columns as $key => $label)
                <td>{{ $row[$key] ?? ($key === 'cost_center' ? '-' : '') }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
