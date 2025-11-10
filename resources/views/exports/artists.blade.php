<table>
    <thead>
        <tr>
            <th>{{ __('export.name_artist', [], $language) }}</th>
            <th>{{ __('export.civil_name', [], $language) }}</th>
            <th>{{ __('export.phone_number', [], $language) }}</th>
            <th>{{ __('export.position', [], $language) }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $artist)
            <tr>
                <td>{{ $artist->name }}</td>
                <td>{{ $artist->civil_name }}</td>
                <td>{{ $artist->phone_number }}</td>
                <td>{{ $artist->position }}</td>
            </tr>
        @endforeach
    </tbody>
</table>