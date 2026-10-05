@component('mail::message', ['body' => $notification, 'pageTitle' => $pageTitle])
    @php
        // Beschreibung (Datum, Zeit, Gewerk, Projekt …) und Deep-Link aus dem Notification-Payload;
        // ohne eigenen Link fällt der Button auf die App-URL zurück.
        // CSS liegt im HTML-Layout (vendor/mail/html/layout), damit der Textteil der Mail sauber bleibt.
        $presenter = \Artwork\Modules\Notification\Support\NotificationMailPresenter::class;
        $language = $language ?? config('app.locale');
        $description = $presenter::descriptionOf($notification);
        $textLines = $presenter::textLines($description);
        $primaryLink = $presenter::primaryLink($description);
        $hasDeepLink = $presenter::hasDeepLink($description);
        $notificationEvent = is_object($notification) ? ($notification->event ?? null) : null;
        $eventLine = $presenter::eventLine($notificationEvent, $language);
    @endphp
    <div class="notification">
        <h2>{{ $notification->title }}</h2>
        @if($eventLine !== '')
            <p class="notification-text">{{ $eventLine }}</p>
        @endif
        @foreach($textLines as $line)
            <p class="notification-description">{{ $line }}</p>
        @endforeach
        <a href="{{ $primaryLink }}" class="notification-link">
            @if($hasDeepLink)
                {{ __('Open directly in :app', ['app' => $pageTitle], $language) }}
            @else
                {{ __('View all notifications in :app', ['app' => $pageTitle], $language) }}
            @endif
        </a>
        @if($hasDeepLink)
            <br>
            <a href="{{ $presenter::appUrl() }}" class="notification-link notification-link-secondary">
                {{ __('View all notifications in :app', ['app' => $pageTitle], $language) }}
            </a>
        @endif
    </div>
@endcomponent
