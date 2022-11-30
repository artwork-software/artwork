@component('mail::message', ['notification' => $notification])
    <style>
        .notification {
            margin-bottom: 1.5rem;
            margin-top: 1.5rem;
            padding: 10px;
            font-family: Inter, serif;
        }
        h2 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 0.2rem;
            color: #27233C;
        }
        .notification-text {
            font-size: 12px;
            font-weight: 500;
        }
    </style>
    <div class="notification">
        <p>
            Hallo, <br>
            es gibt Neuigkeiten in deinem artwork!
        </p>
        <h2>{{ $notification['title'] }}</h2>
        <p class="notification-text">
            @if($notification['type']->value === 'ROOM_REQUEST')
                {{ \App\Models\Room::find($notification['event']['room_id'])->first()->name }} | {{ \App\Models\EventType::find($notification['event']['event_type_id'])->first()->name }},
                {{ $notification['event']['eventName'] }} | @if(!empty($notification['event']['project_id'])) {{ \App\Models\Project::find($notification['event']['project_id'])->first()->name }} | @endif
                {{ date('d.m.Y H:i', strtotime($notification['event']['start_time'])) }} -  {{ date('d.m.Y H:i', strtotime($notification['event']['end_time'])) }}
            @endif
            @if($notification['type']->value === 'NOTIFICATION_UPSERT_ROOM_REQUEST')
                {{ \App\Models\EventType::find($notification['event']['event_type_id'])->first()->name }},
                {{ $notification['event']['eventName'] }} | @if(!empty($notification['event']['project_id'])) {{ \App\Models\Project::find($notification['event']['project_id'])->first()->name }} | @endif
                {{ date('d.m.Y H:i', strtotime($notification['event']['start_time'])) }} -  {{ date('d.m.Y H:i', strtotime($notification['event']['end_time'])) }}
            @endif
            @if($notification['type']->value === 'NOTIFICATION_CONFLICT')
                {{ \App\Models\Room::find($notification['conflict']['event']['room_id'])->first()->name }} | {{ \App\Models\EventType::find($notification['conflict']['event']['event_type_id'])->first()->name }},
                {{ $notification['conflict']['event']['eventName'] }} | @if(!empty($notification['conflict']['event']['project_id'])) {{ \App\Models\Project::find($notification['conflict']['event']['project_id'])->first()->name }} | @endif
                {{ date('d.m.Y H:i', strtotime($notification['conflict']['event']['start_time'])) }} -  {{ date('d.m.Y H:i', strtotime($notification['conflict']['event']['end_time'])) }}
            @endif
            @if($notification['type']->value === 'NOTIFICATION_EVENT_CHANGED')
                {{ \App\Models\Room::find($notification['event']['room_id'])->first()->name }} | {{ \App\Models\EventType::find($notification['event']['event_type_id'])->first()->name }},
                {{ $notification['event']['eventName'] }} | @if(!empty($notification['conflict']['event']['project_id'])) {{ \App\Models\Project::find($notification['event']['project_id'])->first()->name }} | @endif
                {{ date('d.m.Y H:i', strtotime($notification['event']['start_time'])) }} -  {{ date('d.m.Y H:i', strtotime($notification['event']['end_time'])) }}
            @endif
            @if($notification['type']->value === 'NOTIFICATION_LOUD_ADJOINING_EVENT')
                {{ \App\Models\Room::find($notification['conflict']['room_id'])->first()->name }} | {{ \App\Models\EventType::find($notification['conflict']['event_type_id'])->first()->name }},
                {{ $notification['conflict']['eventName'] }} | @if(!empty($notification['conflict']['project_id'])) {{ \App\Models\Project::find($notification['conflict']['project_id'])->first()->name }} | @endif
                {{ date('d.m.Y H:i', strtotime($notification['conflict']['start_time'])) }} -  {{ date('d.m.Y H:i', strtotime($notification['conflict']['end_time'])) }}
            @endif
        </p>
    </div>

@endcomponent
