<?php

namespace Artwork\Core\Sentry;

use Sentry\Event;
use Sentry\EventHint;

/**
 * before_send-Hook (config/sentry.php): Passwörter und 2FA-Codes dürfen nie mit dem Request-Body an
 * Sentry gehen – der Body wird unabhängig von send_default_pii mitgeschickt. Gilt für alle Requests
 * (Web-Login, Passwort ändern, App-Login …). Statisch, damit config:cache die Konfiguration
 * serialisieren kann.
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
    ];

    public static function beforeSend(Event $event, ?EventHint $hint = null): ?Event
    {
        $request = $event->getRequest();
        if (!array_key_exists('data', $request)) {
            return $event;
        }

        $request['data'] = self::scrub($request['data']);
        $event->setRequest($request);

        return $event;
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
