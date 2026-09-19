<?php

namespace Artwork\Modules\Notification\Support;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Throwable;

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

    /**
     * Termin-Zeile „Raum, Terminart | Name | Projekt | Beginn - Ende“ aus dem Event des Payloads.
     * Akzeptiert das Event-Modell (Sofort-Mail), die stdClass bzw. das JSON-Array einer
     * DatabaseNotification (Sammelmail). Raum/Terminart/Projekt werden über ihre IDs aufgelöst —
     * ausschließlich per find(); ein nachgeschaltetes first() würde den ERSTEN Datensatz der Tabelle liefern.
     */
    public static function eventLine(mixed $event, ?string $language = null): string
    {
        if (is_object($event) && !method_exists($event, 'toArray')) {
            $event = (array) $event;
        } elseif (is_object($event)) {
            $event = $event->getAttributes();
        }

        if (!is_array($event) || $event === []) {
            return '';
        }

        $parts = [];
        $roomId = $event['room_id'] ?? null;
        if (!empty($roomId)) {
            $parts[] = Room::query()->find($roomId)?->name ?? __('Event without room', [], $language);
        }

        $typeId = $event['event_type_id'] ?? null;
        $typeName = !empty($typeId) ? (EventType::query()->find($typeId)?->name ?? '') : '';
        $eventName = trim((string) ($event['eventName'] ?? ''));
        $parts[] = trim($typeName . ($typeName !== '' && $eventName !== '' ? ' | ' : '') . $eventName);

        $projectId = $event['project_id'] ?? null;
        if (!empty($projectId)) {
            $parts[] = Project::query()->find($projectId)?->name ?? __('No Project', [], $language);
        }

        $start = self::formatDateTime($event['start_time'] ?? null);
        $end = self::formatDateTime($event['end_time'] ?? null);
        if ($start !== '' || $end !== '') {
            $parts[] = trim($start . ' - ' . $end, ' -');
        }

        return implode(' | ', array_filter($parts, static fn (string $part): bool => $part !== ''));
    }

    /**
     * Zeitangaben kommen als Carbon (Modell), als Cast-String „12. Oct 2026 09:00“ (JSON der
     * DatabaseNotification) oder ISO-String; ungültige Werte ergeben eine leere Angabe statt 01.01.1970.
     */
    public static function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d.m.Y H:i');
        } catch (Throwable) {
            return '';
        }
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
