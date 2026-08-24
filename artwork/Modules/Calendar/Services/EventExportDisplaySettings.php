<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\User\Models\UserCalendarSettings;

/**
 * Anzeigeeinstellungen eines Kalender-Exports: die Kalender-Anzeigeeinstellungen des Users
 * dienen als Default, das Export-Modal kann einzelne Flags pro Export übersteuern (ohne die
 * gespeicherten User-Settings anzufassen). Bündelt zugleich die Farb-/Titel-Logik der
 * Kalender-Kacheln (FullEventInCalendar/CompactEventInCalendar) für alle Export-Renderer.
 */
class EventExportDisplaySettings
{
    /** Flags, die das Export-Modal übersteuern darf (Spalten von user_calendar_settings). */
    public const OVERRIDABLE_KEYS = [
        'use_event_status_color',
        'use_main_category_color',
        'show_artist_names_as_title',
        'event_name',
        'description',
        'project_artists',
        'project_status',
        'project_management',
        'show_event_creator',
        'show_event_admission',
        'show_event_status',
        'show_day_remarks',
        'hide_unoccupied_rooms',
        'show_planned_events',
    ];

    /** Spalten-Defaults für User ohne user_calendar_settings-Zeile (alle übrigen Flags: false). */
    private const DEFAULTS = [
        'event_name' => true,
        'show_event_admission' => true,
        'show_day_remarks' => true,
    ];

    private function __construct(private readonly UserCalendarSettings $settings)
    {
    }

    /**
     * @param mixed $overrides `displaySettings` aus dem Request (oder null)
     */
    public static function fromRequest(mixed $overrides, ?UserCalendarSettings $userCalendarSettings): self
    {
        $attributes = self::DEFAULTS;

        if ($userCalendarSettings !== null) {
            $attributes = array_replace($attributes, $userCalendarSettings->attributesToArray());
            unset($attributes['id'], $attributes['user_id'], $attributes['created_at'], $attributes['updated_at']);
        }

        if (is_array($overrides)) {
            foreach (self::OVERRIDABLE_KEYS as $key) {
                if (array_key_exists($key, $overrides)) {
                    $attributes[$key] = filter_var($overrides[$key], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        $settings = new UserCalendarSettings();
        $settings->setRawAttributes($attributes);
        $settings->exists = false;

        return new self($settings);
    }

    /**
     * In-Memory-Settings für Raum-/Terminselektion (hide_unoccupied_rooms, show_planned_events).
     * Nie persistieren — exists ist bewusst false.
     */
    public function settings(): UserCalendarSettings
    {
        return $this->settings;
    }

    public function shows(string $key): bool
    {
        return (bool) $this->settings->getAttribute($key);
    }

    /**
     * Kachelfarbe wie im Kalender: Hauptkategorie > Terminstatus > Terminart.
     * Grau (#9E9E9E) für Termine ohne Projekt, Anthrazit (#3A3A3A) für Projekte ohne Hauptkategorie.
     */
    public function resolveColor(
        ?object $eventType,
        ?object $eventStatus,
        bool $hasProject,
        ?string $mainCategoryColor,
        string $fallback = '#9E9E9E'
    ): string {
        if ($this->shows('use_main_category_color')) {
            if (!$hasProject) {
                return '#9E9E9E';
            }

            return $mainCategoryColor ?: '#3A3A3A';
        }

        if ($this->shows('use_event_status_color') && ($eventStatus->color ?? null)) {
            return $eventStatus->color;
        }

        return $eventType->hex_code ?? $fallback;
    }

    /**
     * Primärer Kachelname: Künstler:innen statt Termintitel, wenn eingestellt;
     * ohne aktives event_name-Flag entfällt der Terminname.
     */
    public function resolveEventName(?string $eventName, ?string $artistNames): ?string
    {
        if ($this->shows('show_artist_names_as_title') && $artistNames) {
            return $artistNames;
        }

        return $this->shows('event_name') ? $eventName : null;
    }

    /**
     * Zusätzliche Inhaltszeilen einer Termin-Kachel gemäß aktivierter Flags.
     * Erwartet ein PdfEventDTO-förmiges Objekt (artistNames, projectStatus, eventStatus,
     * description, projectLeaders, createdBy — fehlende Felder werden übersprungen).
     *
     * @return array<int, string>
     */
    public function extraContentLines(object $event): array
    {
        $primaryName = $this->resolveEventName($event->eventName ?? null, $event->artistNames ?? null);

        $lines = [];
        if (
            $this->shows('project_artists')
            && ($event->artistNames ?? null)
            && $event->artistNames !== $primaryName
        ) {
            $lines[] = $event->artistNames;
        }
        if ($this->shows('project_status') && ($event->projectStatus ?? null)) {
            $lines[] = $event->projectStatus;
        }
        if ($this->shows('show_event_status') && ($event->eventStatus->name ?? null)) {
            $lines[] = $event->eventStatus->name;
        }
        if ($this->shows('description') && ($event->description ?? null)) {
            $lines[] = $event->description;
        }
        if ($this->shows('project_management') && !empty($event->projectLeaders)) {
            $lines[] = implode(', ', $event->projectLeaders);
        }
        if ($this->shows('show_event_creator') && ($event->createdBy ?? null)) {
            $lines[] = $event->createdBy;
        }

        return $lines;
    }
}
