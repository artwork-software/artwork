@component(
    'mail::message',
    [
        'notifications' => $notifications,
        'user' => $user,
        'page_title' => $page_title
    ]
)
    @php
        $page_title = $page_title !== '' ? $page_title : 'Artwork';
        $language = $language ?? config('app.locale');
        // Je Eintrag: Termin-Zeile, Beschreibungszeilen (Datum, Zeit, Gewerk, Projekt …) + Deep-Link aus dem Payload.
        // CSS liegt im HTML-Layout (vendor/mail/html/layout), damit der Textteil der Mail sauber bleibt.
        $presenter = \Artwork\Modules\Notification\Support\NotificationMailPresenter::class;
    @endphp
    <div class="email-content">
        <p class="email-greeting">
            {{ __('Hello :name,', ['name' => $user], $language) }}<br>
            {{ __('There is news in :app', ['app' => $page_title], $language) }}
        </p>
        @foreach($notifications as $notification)
            <div class="notification-group">
                <h1>{{ $notification['title'] }} <span>{{ $notification['count'] }}</span></h1>
                @foreach($notification['notifications'] as $body)
                    @php
                        $entry = $body['body'] ?? [];
                        $entryDescription = $presenter::descriptionOf($entry);
                        $eventLine = $presenter::eventLine($entry['event'] ?? null, $language);
                    @endphp
                    <div class="notification-content">
                        <div class="notification-text">
                            <h2>{{ $entry['title'] ?? '' }}</h2>
                            @if($eventLine !== '')
                                <p>{{ $eventLine }}</p>
                            @endif
                            @foreach($presenter::textLines($entryDescription) as $line)
                                <p class="notification-description">{{ $line }}</p>
                            @endforeach
                            @if($presenter::hasDeepLink($entryDescription))
                                <a href="{{ $presenter::primaryLink($entryDescription) }}" class="notification-link">
                                    {{ __('Open directly in :app', ['app' => $page_title], $language) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
        <a href="{{ $presenter::appUrl() }}" class="notification-link notification-link-footer">
            {{ __('View all notifications in :app', ['app' => $page_title], $language) }}
        </a>
    </div>
@endcomponent
