@component('mail::message', ['body' => $notification, 'pageTitle' => $pageTitle])
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
        <h2>{{ $notification->title }}</h2>
        <p class="notification-text">
            @if(!empty($notification->event))
                @if(!empty($notification->event->room_id))
                    {{ \Artwork\Modules\Room\Models\Room::query()->find($notification->event->room_id)->first()?->name ?? 'Termin ohne Raum' }},
                @endif
                {{ \Artwork\Modules\EventType\Models\EventType::query()->find($notification->event->event_type_id)->first()?->name }}
                |
                {{ $notification->event->eventName }}
                | @if(!empty($notification->event->project_id))
                    {{ \Artwork\Modules\Project\Models\Project::query()->find($notification->event->project_id)->first()?->name ?? 'Kein Projekt' }}
                    |
                @endif
                {{ date('d.m.Y H:i', strtotime($notification->event->start_time)) }}
                - {{ date('d.m.Y H:i', strtotime($notification->event->end_time)) }}
            @endif
        </p>
        <a href="{{ config('app.url') }}"
           style="margin-bottom: 2rem; font-size: 12px; text-decoration: none; color: #3017AD; padding-bottom: 2rem; margin-top: 2rem">
            alle Benachrichtigungen im {{$pageTitle}} ansehen
        </a>
    </div>
@endcomponent
