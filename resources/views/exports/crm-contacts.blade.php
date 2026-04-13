<table>
    <thead>
        <tr>
            @if(in_array('display_name', $columns))
                <th style="background-color: #cccccc; font-weight: bold;">Name</th>
            @endif
            @if(in_array('contact_type', $columns))
                <th style="background-color: #cccccc; font-weight: bold;">{{ __('Contact type') }}</th>
            @endif
            @if(in_array('created_at', $columns))
                <th style="background-color: #cccccc; font-weight: bold;">{{ __('Created at') }}</th>
            @endif
            @foreach($properties as $property)
                @if(in_array('prop_' . $property->id, $columns))
                    <th style="background-color: #cccccc; font-weight: bold;">{{ $property->name }}</th>
                @endif
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($contacts as $contact)
            <tr>
                @if(in_array('display_name', $columns))
                    <td>{{ $contact->display_name }}</td>
                @endif
                @if(in_array('contact_type', $columns))
                    <td>{{ $contact->contactType?->name }}</td>
                @endif
                @if(in_array('created_at', $columns))
                    <td>{{ $contact->created_at?->format('d.m.Y H:i') }}</td>
                @endif
                @foreach($properties as $property)
                    @if(in_array('prop_' . $property->id, $columns))
                        <td>{{ $contact->propertyValues->firstWhere('crm_property_id', $property->id)?->value }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
