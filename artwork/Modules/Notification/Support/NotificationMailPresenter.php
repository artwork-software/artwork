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
     * Buttons, deren Aktion es nur im Benachrichtigungscenter gibt (Annehmen/Ablehnen, Antworten,
     * Prüfen …). Der erste Beschreibungslink führte dort z. B. auf die Raumseite – ohne Aktion.
     */
    private const IN_APP_ACTIONS = [
        'accept',
        'decline',
        'answer',
        'answerDialog',
        'change_request',
        'event_delete',
        'calculation_check',
        'delete_request',
        'material_issue_return_confirm',
        'material_issue_return_decline',
    ];

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
     * Erster absoluter Link aus der Beschreibung; Fallback ist das Benachrichtigungscenter.
     */
    public static function primaryLink(mixed $description): string
    {
        foreach (self::descriptionRows($description) as $row) {
            if ($row['href'] !== null) {
                return $row['href'];
            }
        }

        return self::notificationsUrl();
    }

    /**
     * Ziel des Haupt-Buttons einer Mail: das Benachrichtigungscenter, wenn dort eine Aktion
     * wartet, sonst der erste Beschreibungslink (bzw. das Center als Fallback).
     */
    public static function mainLink(mixed $payload): string
    {
        return self::requiresAppAction($payload)
            ? self::notificationsUrl()
            : self::primaryLink(self::descriptionOf($payload));
    }

    public static function hasMainLink(mixed $payload): bool
    {
        return self::requiresAppAction($payload) || self::hasDeepLink(self::descriptionOf($payload));
    }

    public static function requiresAppAction(mixed $payload): bool
    {
        $buttons = match (true) {
            is_object($payload) => $payload->buttons ?? [],
            is_array($payload) => $payload['buttons'] ?? [],
            default => [],
        };

        return array_intersect((array) $buttons, self::IN_APP_ACTIONS) !== [];
    }

    public static function notificationsUrl(): string
    {
        return route('notifications.index');
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
     * aus $lookups (eventLookups(), Sammelmail: drei Abfragen für die ganze Mail statt drei je Eintrag)
     * oder einzeln per find(); ein nachgeschaltetes first() würde den ERSTEN Datensatz der Tabelle liefern.
     *
     * @param array<string, array<int, string>>|null $lookups rooms/eventTypes/projects: ID → Name
     */
    public static function eventLine(mixed $event, ?string $language = null, ?array $lookups = null): string
    {
        $event = self::eventAttributes($event);
        if ($event === []) {
            return '';
        }

        $parts = [];
        $roomId = $event['room_id'] ?? null;
        if (!empty($roomId)) {
            $roomName = $lookups !== null
                ? ($lookups['rooms'][(int) $roomId] ?? null)
                : Room::query()->find($roomId)?->name;
            $parts[] = $roomName ?? __('Event without room', [], $language);
        }

        $typeId = $event['event_type_id'] ?? null;
        $typeName = '';
        if (!empty($typeId)) {
            $typeName = $lookups !== null
                ? ($lookups['eventTypes'][(int) $typeId] ?? '')
                : (EventType::query()->find($typeId)?->name ?? '');
        }
        $eventName = trim((string) ($event['eventName'] ?? ''));
        $parts[] = trim($typeName . ($typeName !== '' && $eventName !== '' ? ' | ' : '') . $eventName);

        $projectId = $event['project_id'] ?? null;
        if (!empty($projectId)) {
            $projectName = $lookups !== null
                ? ($lookups['projects'][(int) $projectId] ?? null)
                : Project::query()->find($projectId)?->name;
            $parts[] = $projectName ?? __('No Project', [], $language);
        }

        $start = self::formatDateTime($event['start_time'] ?? null);
        $end = self::formatDateTime($event['end_time'] ?? null);
        if ($start !== '' || $end !== '') {
            $parts[] = trim($start . ' - ' . $end, ' -');
        }

        return implode(' | ', array_filter($parts, static fn (string $part): bool => $part !== ''));
    }

    /**
     * Namen von Raum, Terminart und Projekt aller Termine in den Payloads, je Tabelle eine Abfrage –
     * für eventLine() in der Sammelmail.
     *
     * @param iterable<mixed> $payloads
     * @return array{rooms: array<int, string>, eventTypes: array<int, string>, projects: array<int, string>}
     */
    public static function eventLookups(iterable $payloads): array
    {
        $ids = ['rooms' => [], 'eventTypes' => [], 'projects' => []];
        foreach ($payloads as $payload) {
            $event = self::eventAttributes(match (true) {
                is_array($payload) => $payload['event'] ?? null,
                is_object($payload) => $payload->event ?? null,
                default => null,
            });
            $columns = ['rooms' => 'room_id', 'eventTypes' => 'event_type_id', 'projects' => 'project_id'];
            foreach ($columns as $key => $column) {
                if (!empty($event[$column]) && is_numeric($event[$column])) {
                    $ids[$key][] = (int) $event[$column];
                }
            }
        }

        $namesOf = static fn (string $model, array $modelIds): array => $modelIds === []
            ? []
            : $model::query()->whereKey(array_values(array_unique($modelIds)))->pluck('name', 'id')
                ->map(static fn (mixed $name): string => (string) $name)
                ->all();

        return [
            'rooms' => $namesOf(Room::class, $ids['rooms']),
            'eventTypes' => $namesOf(EventType::class, $ids['eventTypes']),
            'projects' => $namesOf(Project::class, $ids['projects']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function eventAttributes(mixed $event): array
    {
        if (is_object($event) && !method_exists($event, 'toArray')) {
            $event = (array) $event;
        } elseif (is_object($event)) {
            $event = $event->getAttributes();
        }

        return is_array($event) ? $event : [];
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
