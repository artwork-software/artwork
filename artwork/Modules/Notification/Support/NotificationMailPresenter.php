<?php

namespace Artwork\Modules\Notification\Support;

use Illuminate\Config\Repository;

/**
 * Bereitet den Notification-Payload (stdClass aus NotificationService::createNotification
 * bzw. das JSON-dekodierte Array einer DatabaseNotification) für die Mail-Templates auf:
 * Beschreibungszeilen als Text und ein absoluter Deep-Link, der auf die App-URL zurückfällt.
 */
final class NotificationMailPresenter
{
    /**
     * Beschreibungszeilen der Notification als flache Liste.
     *
     * @return array<int, array{type: string, title: string, href: string|null}>
     */
    public static function descriptionRows(mixed $description): array
    {
        if ($description === null) {
            return [];
        }

        $rows = [];
        foreach ((array) $description as $row) {
            $row = is_object($row) ? (array) $row : $row;
            if (!is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $rows[] = [
                'type' => (string) ($row['type'] ?? 'string'),
                'title' => $title,
                'href' => self::absoluteUrl($row['href'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * Textzeilen (alles außer type=link) — Datum, Zeit, Gewerk, Projekt usw.
     *
     * @return array<int, string>
     */
    public static function textLines(mixed $description): array
    {
        return array_values(array_map(
            static fn (array $row): string => $row['title'],
            array_filter(
                self::descriptionRows($description),
                static fn (array $row): bool => $row['type'] !== 'link'
            )
        ));
    }

    /**
     * Erster absoluter Link aus der Beschreibung; Fallback ist die App-URL.
     */
    public static function primaryLink(mixed $description): string
    {
        foreach (self::descriptionRows($description) as $row) {
            if ($row['href'] !== null) {
                return $row['href'];
            }
        }

        return self::appUrl();
    }

    /**
     * Ob die Notification einen eigenen Deep-Link mitbringt (sonst nur App-URL).
     */
    public static function hasDeepLink(mixed $description): bool
    {
        foreach (self::descriptionRows($description) as $row) {
            if ($row['href'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Beschreibung aus einem Payload lesen — stdClass (ShiftNotification) oder Array (Sammelmail).
     */
    public static function descriptionOf(mixed $payload): mixed
    {
        if (is_object($payload)) {
            return $payload->description ?? null;
        }

        if (is_array($payload)) {
            return $payload['description'] ?? null;
        }

        return null;
    }

    public static function appUrl(): string
    {
        return rtrim((string) app(Repository::class)->get('app.url'), '/');
    }

    /**
     * route() liefert bereits absolute URLs; relative Pfade (z. B. aus älteren Payloads)
     * werden mit der App-URL vervollständigt. Leere/ungültige Werte → null.
     */
    public static function absoluteUrl(mixed $href): ?string
    {
        if (!is_string($href)) {
            return null;
        }

        $href = trim($href);
        if ($href === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        if (str_starts_with($href, '/')) {
            return self::appUrl() . $href;
        }

        return null;
    }
}
