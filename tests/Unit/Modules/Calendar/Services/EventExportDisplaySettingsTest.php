<?php

namespace Tests\Unit\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\EventExportDisplaySettings;
use Artwork\Modules\User\Models\UserCalendarSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventExportDisplaySettingsTest extends TestCase
{
    #[Test]
    public function defaults_apply_when_user_has_no_calendar_settings_row(): void
    {
        $display = EventExportDisplaySettings::fromRequest(null, null);

        // Default-true-Spalten (DB-Defaults) müssen auch ohne Settings-Zeile greifen
        $this->assertTrue($display->shows('event_name'));
        $this->assertTrue($display->shows('show_event_admission'));
        $this->assertTrue($display->shows('show_day_remarks'));
        $this->assertFalse($display->shows('use_main_category_color'));
        $this->assertFalse($display->shows('project_artists'));
    }

    #[Test]
    public function request_overrides_win_over_user_settings(): void
    {
        $userSettings = new UserCalendarSettings();
        $userSettings->setRawAttributes([
            'use_main_category_color' => true,
            'project_artists' => true,
            'event_name' => true,
        ]);

        $display = EventExportDisplaySettings::fromRequest(
            ['use_main_category_color' => false, 'description' => true],
            $userSettings
        );

        $this->assertFalse($display->shows('use_main_category_color'));
        $this->assertTrue($display->shows('description'));
        // Nicht übersteuerte Flags behalten den User-Wert
        $this->assertTrue($display->shows('project_artists'));
    }

    #[Test]
    public function unknown_override_keys_are_ignored(): void
    {
        $display = EventExportDisplaySettings::fromRequest(
            ['work_shifts' => true, 'nonsense' => true],
            null
        );

        $this->assertFalse($display->shows('work_shifts'));
        $this->assertFalse($display->shows('nonsense'));
    }

    #[Test]
    public function resolve_color_mirrors_calendar_tile_logic(): void
    {
        $eventType = (object) ['hex_code' => '#123456'];
        $eventStatus = (object) ['color' => '#ABCDEF'];

        $byType = EventExportDisplaySettings::fromRequest(null, null);
        $this->assertSame('#123456', $byType->resolveColor($eventType, $eventStatus, true, '#FF0000'));

        $byStatus = EventExportDisplaySettings::fromRequest(['use_event_status_color' => true], null);
        $this->assertSame('#ABCDEF', $byStatus->resolveColor($eventType, $eventStatus, true, '#FF0000'));
        // Ohne Status-Farbe fällt die Statusquelle auf die Terminart zurück (wie im Kalender)
        $this->assertSame('#123456', $byStatus->resolveColor($eventType, null, true, '#FF0000'));

        $byCategory = EventExportDisplaySettings::fromRequest(['use_main_category_color' => true], null);
        $this->assertSame('#FF0000', $byCategory->resolveColor($eventType, $eventStatus, true, '#FF0000'));
        $this->assertSame('#3A3A3A', $byCategory->resolveColor($eventType, $eventStatus, true, null));
        $this->assertSame('#9E9E9E', $byCategory->resolveColor($eventType, $eventStatus, false, null));

        // Fallback, wenn weder Terminart noch andere Quelle greift
        $this->assertSame('#111111', $byType->resolveColor(null, null, false, null, '#111111'));
    }

    #[Test]
    public function resolve_event_name_swaps_in_artist_names_and_respects_event_name_flag(): void
    {
        $default = EventExportDisplaySettings::fromRequest(null, null);
        $this->assertSame('Premiere', $default->resolveEventName('Premiere', 'Anna Artist'));

        $artistTitle = EventExportDisplaySettings::fromRequest(['show_artist_names_as_title' => true], null);
        $this->assertSame('Anna Artist', $artistTitle->resolveEventName('Premiere', 'Anna Artist'));
        // Ohne Künstler:innen bleibt der Terminname stehen
        $this->assertSame('Premiere', $artistTitle->resolveEventName('Premiere', null));

        $noEventName = EventExportDisplaySettings::fromRequest(['event_name' => false], null);
        $this->assertNull($noEventName->resolveEventName('Premiere', 'Anna Artist'));
    }

    #[Test]
    public function extra_content_lines_follow_the_flags_and_skip_duplicate_artists(): void
    {
        $event = (object) [
            'eventName' => 'Premiere',
            'artistNames' => 'Anna Artist',
            'projectStatus' => 'In Planung',
            'eventStatus' => (object) ['name' => 'Bestätigt'],
            'description' => 'Beschreibungstext',
            'projectLeaders' => ['Lea Leitung'],
            'createdBy' => 'Erik Ersteller',
        ];

        $none = EventExportDisplaySettings::fromRequest(null, null);
        $this->assertSame([], $none->extraContentLines($event));

        $all = EventExportDisplaySettings::fromRequest([
            'project_artists' => true,
            'project_status' => true,
            'show_event_status' => true,
            'description' => true,
            'project_management' => true,
            'show_event_creator' => true,
        ], null);
        $this->assertSame(
            ['Anna Artist', 'In Planung', 'Bestätigt', 'Beschreibungstext', 'Lea Leitung', 'Erik Ersteller'],
            $all->extraContentLines($event)
        );

        // "Künstler:innen statt Titel" aktiv → Künstler:innen nicht doppelt als Zusatzzeile
        $artistTitle = EventExportDisplaySettings::fromRequest([
            'project_artists' => true,
            'show_artist_names_as_title' => true,
        ], null);
        $this->assertSame([], $artistTitle->extraContentLines($event));
    }
}
