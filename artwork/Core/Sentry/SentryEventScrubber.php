<?php

namespace Artwork\Core\Sentry;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;

/**
 * before_send-Hook (config/sentry.php): Passwörter und 2FA-Codes dürfen nie mit dem Request-Body an
 * Sentry gehen – der Body wird unabhängig von send_default_pii mitgeschickt. Gilt für alle Requests
 * (Web-Login, Passwort ändern, App-Login …). Statisch, damit config:cache die Konfiguration
 * serialisieren kann.
 *
 * Außerdem verlassen gültige Tokens aus URLs die Anwendung nicht: Magic-Link, Einladung,
 * Passwort-Reset, Ersteinrichtung und Kalender-Abo stehen sonst in request.url, im Query-String,
 * im Referer, im Transaktionsnamen, in Breadcrumbs und Spans.
 */
class SentryEventScrubber
{
    public const FILTERED = '[Filtered]';

    /** Feldnamen ohne Groß-/Kleinschreibung, auch verschachtelt (z. B. user[password]). */
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'code',
        'recovery_code',
        // Verbindungs-Secrets (LDAP-Bind, OIDC-Client, Sage, Webhooks, API-Schlüssel)
        'bind_password',
        'client_secret',
        'api_key',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'plaintexttoken',
    ];

    /**
     * Pfadabschnitte, deren nächstes Segment ein gültiges Token ist: Magic-Link der Externen,
     * Passwort-Reset mit Token im Pfad und die Kalender-Abo-Feeds. Routen-Platzhalter ("{token}")
     * und die internen Verwaltungsrouten der Abos ("create", "12/update", "12/renew") bleiben lesbar.
     */
    private const TOKEN_PATH_PATTERN = '#(/external/login/|/reset-password/|/calendar/abo/)'
        . '(?!\{|create(?:[/?\#\s]|$)|\d+/(?:update|renew)(?:[/?\#\s]|$))[^/?\#\s"\'<>]+#i';

    /**
     * Query-Parameter mit Token-Charakter: Einladung, Passwort-Reset, Ersteinrichtung und die
     * Signatur signierter Links.
     */
    private const TOKEN_QUERY_PATTERN = '#(^|[?&;])((?:token|access_token|refresh_token|signature|code|'
        . 'recovery_code|password|secret|client_secret|api_key)=)[^&\#\s"\'<>]*#i';

    public static function beforeSend(Event $event, ?EventHint $hint = null): ?Event
    {
        $request = $event->getRequest();
        if (array_key_exists('data', $request)) {
            $request['data'] = self::scrub($request['data']);
        }

        $event->setRequest(self::scrubUrls($request));
        self::scrubEventUrls($event);

        return $event;
    }

    /**
     * before_send_transaction-Hook: Performance-Transaktionen tragen dieselben URLs (Name, Request,
     * Trace-Kontext, Spans) und durchlaufen before_send nicht.
     */
    public static function beforeSendTransaction(Event $event, ?EventHint $hint = null): ?Event
    {
        return self::beforeSend($event, $hint);
    }

    /**
     * Entfernt Tokens aus einer URL, einem Pfad oder einem Query-String. Der Rest bleibt erhalten,
     * damit der Fehler in Sentry weiter der Route zugeordnet werden kann.
     */
    public static function scrubUrl(string $value): string
    {
        $value = (string) preg_replace(self::TOKEN_PATH_PATTERN, '$1' . self::FILTERED, $value);

        return (string) preg_replace(self::TOKEN_QUERY_PATTERN, '$1$2' . self::FILTERED, $value);
    }

    /**
     * Wendet scrubUrl auf alle Zeichenketten einer verschachtelten Struktur an (Header wie Referer,
     * Breadcrumb-Daten, Span-Daten).
     */
    public static function scrubUrls(mixed $data): mixed
    {
        if (is_string($data)) {
            return self::scrubUrl($data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::scrubUrls($value);
            }
        }

        return $data;
    }

    /**
     * Alle Stellen eines Events außerhalb des Request-Blocks, an denen URLs landen: Transaktionsname,
     * Tags, Extra, Kontexte (Trace-Daten), Breadcrumbs und Spans.
     */
    private static function scrubEventUrls(Event $event): void
    {
        $transaction = $event->getTransaction();
        if ($transaction !== null) {
            $event->setTransaction(self::scrubUrl($transaction));
        }

        $event->setTags(self::scrubUrls($event->getTags()));
        $event->setExtra(self::scrubUrls($event->getExtra()));

        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, self::scrubUrls($context));
        }

        $event->setBreadcrumb(array_map(self::scrubBreadcrumb(...), $event->getBreadcrumbs()));

        foreach ($event->getSpans() as $span) {
            $description = $span->getDescription();
            if ($description !== null) {
                $span->setDescription(self::scrubUrl($description));
            }

            $span->setData(self::scrubUrls($span->getData()));
        }
    }

    private static function scrubBreadcrumb(Breadcrumb $breadcrumb): Breadcrumb
    {
        $message = $breadcrumb->getMessage();
        if ($message !== null) {
            $breadcrumb = $breadcrumb->withMessage(self::scrubUrl($message));
        }

        foreach ($breadcrumb->getMetadata() as $name => $value) {
            $breadcrumb = $breadcrumb->withMetadata((string) $name, self::scrubUrls($value));
        }

        return $breadcrumb;
    }

    public static function scrub(mixed $data): mixed
    {
        if (is_array($data)) {
            $scrubbed = [];
            foreach ($data as $key => $value) {
                $scrubbed[$key] = is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)
                    ? self::FILTERED
                    : self::scrub($value);
            }

            return $scrubbed;
        }

        // Nicht zerlegbarer Roh-Body (z. B. ungültiges JSON): lieber ganz verwerfen, wenn er ein
        // sensibles Feld nennen könnte
        if (is_string($data) && self::mentionsSensitiveKey($data)) {
            return self::FILTERED;
        }

        return $data;
    }

    private static function mentionsSensitiveKey(string $data): bool
    {
        $lowered = strtolower($data);
        foreach (self::SENSITIVE_KEYS as $key) {
            if (str_contains($lowered, $key)) {
                return true;
            }
        }

        return false;
    }
}
