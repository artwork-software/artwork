<table>
    <thead>
        <tr>
        @foreach($columns as $column)
            <th>{{ $column }}</th>
        @endforeach
        </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
        @foreach ($row as $column)
            <td>{{$column}}</td>
        @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
