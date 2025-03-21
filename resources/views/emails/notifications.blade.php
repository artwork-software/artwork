@component(
    'mail::message',
    [
        'notifications' => $notifications,
        'user' => $user,
        'page_title' => $page_title
    ]
)
    @php
        $page_title = $page_title !== '' ?$page_title : 'Artwork';
    @endphp
    <style>
        body {
            font-family: Inter, serif;
        }

        h1 {
            font-family: Inter, serif;
            color: #27233C;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-content: center;
            align-items: center;
        }

        h1 span {
            font-size: 12px;
            font-family: Inter, serif;
            background-color: #EBEBE8;
            border-radius: 5px;
            height: 20px;
            width: 20px;
            min-width: 20px;
            min-height: 20px;
            color: #A7A6B1;
            text-align: center;
            margin-left: 1rem;
            display: flex;
            align-items: center;
            align-content: center;
            justify-content: center;
            padding: 5px;
        }

        .email-content {
            padding: 10px;
            margin-bottom: 2rem;
        }

        h2 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 0.2rem;
            color: #27233C;
        }

        .notification-text p {
            font-size: 12px;
            font-weight: 500;
        }

        .notification-content {
            margin-bottom: 1rem;
        }

    </style>
    <div class="email-content">
        <p style="margin-bottom: 2rem; font-size: 16px; font-family: Inter, sans-serif; font-weight: 500; margin-top: 3rem">
            @php echo 'Hallo ' . $user . ',<br> es gibt Neuigkeiten in ' . $page_title @endphp
        </p>
        @foreach($notifications as $notification)
            <div style="margin-bottom: 1rem">
                <h1>{{ $notification['title'] }} <span>{{ $notification['count'] }}</span></h1>
                @foreach($notification['notifications'] as $body)
                    <div class="notification-content">
                        <div class="notification-text">
                            <h2>{{ $body['body']['title'] }}</h2>
                            <p>
                                @if(!empty($body['body']['event']))
                                    @if(!empty($body['body']['event']['room_id']))
                                        {{ \Artwork\Modules\Room\Models\Room::query()->find($body['body']['event']['room_id'])->first()?->name ?? 'Termin ohne Raum' }},
                                    @endif
                                    {{ \Artwork\Modules\EventType\Models\EventType::query()->find($body['body']['event']['event_type_id'])->first()?->name }}
                                    |
                                    {{ $body['body']['event']['eventName'] }}
                                    | @if(!empty($body['body']['event']['project_id']))
                                        {{ \Artwork\Modules\Project\Models\Project::query()->find($body['body']['event']['project_id'])->first()?->name ?? 'Kein Projekt' }}
                                        |
                                    @endif
                                    {{ date('d.m.Y H:i', strtotime($body['body']['event']['start_time'])) }}
                                    -  {{ date('d.m.Y H:i', strtotime($body['body']['event']['end_time'])) }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
        <a href="{{ config('app.url') }}" style="margin-bottom: 2rem; font-size: 12px; text-decoration: none; color: #3017AD; padding-bottom: 2rem">alle Benachrichtigungen in {{ $page_title  }} ansehen</a>
    </div>
@endcomponent
