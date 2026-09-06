@component('mail::message', ['body' => $notification, 'pageTitle' => $pageTitle])
    @php
        // Beschreibung (Datum, Zeit, Gewerk, Projekt …) und Deep-Link aus dem Notification-Payload;
        // ohne eigenen Link fällt der Button auf die App-URL zurück.
        $presenter = \Artwork\Modules\Notification\Support\NotificationMailPresenter::class;
        $description = $presenter::descriptionOf($notification);
        $textLines = $presenter::textLines($description);
        $primaryLink = $presenter::primaryLink($description);
        $hasDeepLink = $presenter::hasDeepLink($description);
        $notificationEvent = is_object($notification) ? ($notification->event ?? null) : null;
    @endphp
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

        .notification-description {
            font-size: 12px;
            font-weight: 500;
            margin: 0.2rem 0;
        }

        .notification-link {
            display: inline-block;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            font-size: 12px;
            text-decoration: none;
            color: #3017AD;
        }
    </style>
    <div class="notification">
        <h2>{{ $notification->title }}</h2>
        <p class="notification-text">
            @if(!empty($notificationEvent))
                @if(!empty($notificationEvent->room_id))
                    {{ \Artwork\Modules\Room\Models\Room::query()->find($notificationEvent->room_id)?->name ?? 'Termin ohne Raum' }},
                @endif
                {{ \Artwork\Modules\EventType\Models\EventType::query()->find($notificationEvent->event_type_id)?->name }}
                |
                {{ $notificationEvent->eventName }}
                | @if(!empty($notificationEvent->project_id))
                    {{ \Artwork\Modules\Project\Models\Project::query()->find($notificationEvent->project_id)?->name ?? 'Kein Projekt' }}
                    |
                @endif
                {{ date('d.m.Y H:i', strtotime($notificationEvent->start_time)) }}
                - {{ date('d.m.Y H:i', strtotime($notificationEvent->end_time)) }}
            @endif
        </p>
        @foreach($textLines as $line)
            <p class="notification-description">{{ $line }}</p>
        @endforeach
        <a href="{{ $primaryLink }}" class="notification-link">
            @if($hasDeepLink)
                Direkt in {{ $pageTitle }} öffnen
            @else
                alle Benachrichtigungen im {{ $pageTitle }} ansehen
            @endif
        </a>
        @if($hasDeepLink)
            <br>
            <a href="{{ $presenter::appUrl() }}" class="notification-link" style="margin-top: 0.25rem">
                alle Benachrichtigungen im {{ $pageTitle }} ansehen
            </a>
        @endif
    </div>
@endcomponent
