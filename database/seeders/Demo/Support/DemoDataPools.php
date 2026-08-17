<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * Kuratierte Datenpools für das "Artwork Testhaus".
 *
 * Alles, was die Demo-Seeder an Namen, Strukturen und Texten verwenden,
 * ist hier zentral gepflegt. Die Seeder ziehen aus diesen Pools —
 * deterministisch über DemoRandom.
 */
final class DemoDataPools
{
    public const EMAIL_DOMAIN = 'testhaus.artwork.software';
    public const DEMO_PASSWORD = 'TesthausDemo!2026';

    public const COMPANY_NAME = 'Artwork Testhaus';

    /* -----------------------------------------------------------------
     | Gewerke
     | ----------------------------------------------------------------- */

    public const CRAFTS = [
        'buehne' => ['name' => 'Bühne', 'abbreviation' => 'BÜ', 'color' => '#b45309', 'position' => 1],
        'licht' => ['name' => 'Licht', 'abbreviation' => 'LX', 'color' => '#ca8a04', 'position' => 2],
        'ton' => ['name' => 'Ton', 'abbreviation' => 'TON', 'color' => '#2563eb', 'position' => 3],
        'video' => ['name' => 'Video', 'abbreviation' => 'VID', 'color' => '#7c3aed', 'position' => 4],
        'kostuem' => ['name' => 'Kostüm', 'abbreviation' => 'KOS', 'color' => '#db2777', 'position' => 5],
        'maske' => ['name' => 'Maske', 'abbreviation' => 'MSK', 'color' => '#e11d48', 'position' => 6],
        'requisite' => ['name' => 'Requisite', 'abbreviation' => 'REQ', 'color' => '#059669', 'position' => 7],
        'einlass' => ['name' => 'Einlass & Service', 'abbreviation' => 'EIN', 'color' => '#0891b2', 'position' => 8],
        'azubi' => [
            'name' => 'Auszubildende', 'abbreviation' => 'AZ', 'color' => '#64748b', 'position' => 9,
            'universally_applicable' => true,
        ],
        'vt' => [
            'name' => 'Veranstaltungstechnik allgemein', 'abbreviation' => 'VT', 'color' => '#475569', 'position' => 10,
            'universally_applicable' => true,
        ],
    ];

    /**
     * Funktionen (shift_qualifications). "Mitarbeiter" und "Meister" existieren
     * bereits in jeder Installation und werden nur wiederverwendet — die Scopes
     * im Code hängen an genau diesen Namen.
     */
    public const QUALIFICATIONS = [
        'mitarbeiter' => ['name' => 'Mitarbeiter', 'icon' => 'IconUser', 'position' => 1],
        'meister' => ['name' => 'Meister', 'icon' => 'IconBrandRedhat', 'position' => 2],
        'operator' => ['name' => 'Operator', 'icon' => 'IconAdjustmentsAlt', 'position' => 3],
        'vorarbeiter' => ['name' => 'Vorarbeiter*in', 'icon' => 'IconStar', 'position' => 4],
    ];

    /** Welche Funktionen je Gewerk sinnvoll sind (craft_shift_qualification). */
    public const CRAFT_QUALIFICATIONS = [
        'buehne' => ['mitarbeiter', 'meister', 'vorarbeiter'],
        'licht' => ['mitarbeiter', 'meister', 'operator'],
        'ton' => ['mitarbeiter', 'meister', 'operator'],
        'video' => ['mitarbeiter', 'operator'],
        'kostuem' => ['mitarbeiter', 'meister'],
        'maske' => ['mitarbeiter', 'meister'],
        'requisite' => ['mitarbeiter', 'vorarbeiter'],
        'einlass' => ['mitarbeiter', 'vorarbeiter'],
        'azubi' => ['mitarbeiter'],
        'vt' => ['mitarbeiter', 'meister'],
    ];

    public const SHIFT_TIME_PRESETS = [
        ['name' => 'Frühdienst', 'start_time' => '08:00', 'end_time' => '16:30', 'break_time' => 60],
        ['name' => 'Vorstellungsdienst', 'start_time' => '17:00', 'end_time' => '23:00', 'break_time' => 30],
        ['name' => 'Aufbau ganztags', 'start_time' => '09:00', 'end_time' => '18:00', 'break_time' => 60],
        ['name' => 'Abbau / Nacht', 'start_time' => '22:00', 'end_time' => '02:00', 'break_time' => 0],
    ];

    public const SHIFT_GROUPS = [
        ['name' => 'Aufbau', 'color' => '#b45309', 'icon' => 'IconTool'],
        ['name' => 'Vorstellung', 'color' => '#9e1c60', 'icon' => 'IconStar'],
        ['name' => 'Abbau', 'color' => '#475569', 'icon' => 'IconTruck'],
        ['name' => 'Probe', 'color' => '#2563eb', 'icon' => 'IconRepeat'],
    ];

    /* -----------------------------------------------------------------
     | Verträge, Arbeitszeitmuster, Regeln
     | ----------------------------------------------------------------- */

    public const CONTRACTS = [
        'nv_buehne' => [
            'name' => 'NV Bühne Vollzeit',
            'description' => 'Vollzeitvertrag in Anlehnung an NV Bühne: 40h-Basis, saisonbezogene Freizeitregeln.',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => true,
            'compensation_period' => 90,
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 90,
            'free_sundays_per_season' => 8,
            'free_sundays_per_season_active' => true,
            'annual_vacation_days' => 30,
            'weekly_hours' => 40.0,
        ],
        'haustarif' => [
            'name' => 'Haustarif Technik 39h',
            'description' => 'Haustarif für Technik und Verwaltung: 39h, Überstundenausgleich innerhalb von 6 Monaten.',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => false,
            'compensation_period' => 180,
            'overtime_rule_active' => true,
            'overtime_compensation_period' => 180,
            'free_sundays_per_season' => 0,
            'free_sundays_per_season_active' => false,
            'annual_vacation_days' => 28,
            'weekly_hours' => 39.0,
        ],
        'teilzeit' => [
            'name' => 'Teilzeit 20h',
            'description' => 'Teilzeitvertrag mit 20 Wochenstunden, ohne Saison-Sonderregeln.',
            'free_full_days_per_week' => 4,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => false,
            'compensation_period' => 90,
            'overtime_rule_active' => false,
            'overtime_compensation_period' => 0,
            'free_sundays_per_season' => 0,
            'free_sundays_per_season_active' => false,
            'annual_vacation_days' => 15,
            'weekly_hours' => 20.0,
        ],
    ];

    public const WORK_TIME_PATTERNS = [
        'verwaltung' => [
            'name' => 'Verwaltung Mo–Fr',
            'description' => 'Klassische Bürowoche, Montag bis Freitag je 8 Stunden.',
            'monday' => '08:00', 'tuesday' => '08:00', 'wednesday' => '08:00',
            'thursday' => '08:00', 'friday' => '08:00', 'saturday' => '00:00', 'sunday' => '00:00',
        ],
        'theaterbetrieb' => [
            'name' => 'Theaterbetrieb Di–So',
            'description' => 'Spielbetrieb: Dienstag bis Sonntag, Montag frei.',
            'monday' => '00:00', 'tuesday' => '06:30', 'wednesday' => '06:30',
            'thursday' => '06:30', 'friday' => '06:30', 'saturday' => '07:45', 'sunday' => '07:45',
        ],
        'teilzeit' => [
            'name' => 'Teilzeit vormittags',
            'description' => 'Teilzeit: Montag bis Freitag je 4 Stunden am Vormittag.',
            'monday' => '04:00', 'tuesday' => '04:00', 'wednesday' => '04:00',
            'thursday' => '04:00', 'friday' => '04:00', 'saturday' => '00:00', 'sunday' => '00:00',
        ],
    ];

    /** trigger_type => Werte siehe artwork/Modules/Shift/RuleChecks. */
    public const SHIFT_RULES = [
        [
            'name' => 'Max. 10 Stunden pro Tag',
            'description' => 'Warnt, wenn eine Person an einem Tag mehr als 10 Stunden eingeplant ist.',
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 10,
            'warning_color' => '#f59e0b',
            'default_compensation_days' => 0.5,
            'default_compensation_deadline_days' => 60,
            'contracts' => ['nv_buehne', 'haustarif', 'teilzeit'],
        ],
        [
            'name' => 'Höchstens 6 Arbeitstage in Folge',
            'description' => 'Nach spätestens 6 aufeinanderfolgenden Arbeitstagen muss ein freier Tag liegen.',
            'trigger_type' => 'maxConsecWorkingDays',
            'individual_number_value' => 6,
            'warning_color' => '#ef4444',
            'default_compensation_days' => 1.0,
            'default_compensation_deadline_days' => 90,
            'contracts' => ['nv_buehne', 'haustarif'],
        ],
        [
            'name' => '11 Stunden Ruhezeit',
            'description' => 'Zwischen Dienstende und nächstem Dienstbeginn müssen 11 Stunden Ruhezeit liegen.',
            'trigger_type' => 'restTimeBeforeWorkday',
            'individual_number_value' => 11,
            'warning_color' => '#ef4444',
            'default_compensation_days' => 0.5,
            'default_compensation_deadline_days' => 60,
            'contracts' => ['nv_buehne', 'haustarif'],
        ],
    ];

    /* -----------------------------------------------------------------
     | Haus-Struktur
     | ----------------------------------------------------------------- */

    public const AREAS = [
        'haupthaus' => ['name' => 'Haupthaus', 'color' => '#9e1c60'],
        'probenzentrum' => ['name' => 'Probenzentrum', 'color' => '#2563eb'],
        'werkstaetten' => ['name' => 'Werkstätten & Lager', 'color' => '#475569'],
        'aussenflaeche' => ['name' => 'Außenfläche', 'color' => '#059669'],
    ];

    /**
     * role: main_stage|second_stage|foyer|rehearsal|workshop|outdoor —
     * darüber wählen die Termin-Templates ihre Räume.
     */
    public const ROOMS = [
        ['name' => 'Große Bühne', 'area' => 'haupthaus', 'role' => 'main_stage', 'color' => '#9e1c60',
            'description' => 'Hauptspielstätte, 650 Plätze, variable Bestuhlung.', 'capacity' => 650],
        ['name' => 'Studiobühne', 'area' => 'haupthaus', 'role' => 'second_stage', 'color' => '#c026d3',
            'description' => 'Flexible Spielstätte, 180 Plätze.', 'capacity' => 180],
        ['name' => 'Foyer', 'area' => 'haupthaus', 'role' => 'foyer', 'color' => '#f59e0b',
            'description' => 'Foyer mit Bar, für Konzerte und Empfänge nutzbar.', 'everyone_can_book' => true,
            'capacity' => 120],
        ['name' => 'Probebühne 1', 'area' => 'probenzentrum', 'role' => 'rehearsal', 'color' => '#2563eb',
            'description' => 'Probebühne in Originalmaßen der Großen Bühne.'],
        ['name' => 'Probebühne 2', 'area' => 'probenzentrum', 'role' => 'rehearsal', 'color' => '#0891b2',
            'description' => 'Kleine Probebühne mit Tageslicht.'],
        ['name' => 'Ballettsaal', 'area' => 'probenzentrum', 'role' => 'rehearsal', 'color' => '#7c3aed',
            'description' => 'Ballettsaal mit Schwingboden und Spiegelwand.'],
        ['name' => 'Schreinerei', 'area' => 'werkstaetten', 'role' => 'workshop', 'color' => '#b45309',
            'description' => 'Werkstatt Bühnenbau.'],
        ['name' => 'Malersaal', 'area' => 'werkstaetten', 'role' => 'workshop', 'color' => '#ca8a04',
            'description' => 'Malersaal für Prospekte und Kulissen.'],
        ['name' => 'Lager Technik', 'area' => 'werkstaetten', 'role' => 'workshop', 'color' => '#475569',
            'description' => 'Zentrales Materiallager der Technik.'],
        ['name' => 'Vorplatz', 'area' => 'aussenflaeche', 'role' => 'outdoor', 'color' => '#059669',
            'description' => 'Vorplatz für Open-Air-Formate und Anlieferung.'],
    ];

    public const ROOM_CATEGORIES = ['Spielstätte', 'Probenraum', 'Werkstatt', 'Außenfläche'];
    public const ROOM_ATTRIBUTES = ['Bestuhlung variabel', 'Tageslicht', 'Feste Tonregie', 'Schwingboden', 'Verdunkelbar'];

    /** key => Daten; Flags steuern Schicht-/Inventarrelevanz in den Templates. */
    public const EVENT_TYPES = [
        'vorstellung' => ['name' => 'Vorstellung', 'abbreviation' => 'VS', 'hex_code' => '#9e1c60',
            'relevant_for_shift' => true, 'project_mandatory' => true, 'relevant_for_project_period' => true],
        'probe' => ['name' => 'Probe', 'abbreviation' => 'PR', 'hex_code' => '#2563eb',
            'relevant_for_shift' => true, 'project_mandatory' => true],
        'generalprobe' => ['name' => 'Generalprobe', 'abbreviation' => 'GP', 'hex_code' => '#7c3aed',
            'relevant_for_shift' => true, 'project_mandatory' => true, 'relevant_for_project_period' => true],
        'aufbau' => ['name' => 'Aufbau', 'abbreviation' => 'AUF', 'hex_code' => '#b45309',
            'relevant_for_shift' => true, 'relevant_for_inventory' => true],
        'abbau' => ['name' => 'Abbau', 'abbreviation' => 'ABB', 'hex_code' => '#475569',
            'relevant_for_shift' => true, 'relevant_for_inventory' => true],
        'anlieferung' => ['name' => 'Anlieferung', 'abbreviation' => 'ANL', 'hex_code' => '#0891b2',
            'relevant_for_inventory' => true],
        'fuehrung' => ['name' => 'Führung', 'abbreviation' => 'FÜ', 'hex_code' => '#059669'],
        'sonderveranstaltung' => ['name' => 'Sonderveranstaltung', 'abbreviation' => 'SV', 'hex_code' => '#db2777',
            'relevant_for_shift' => true],
        'wartung' => ['name' => 'Wartung', 'abbreviation' => 'WA', 'hex_code' => '#64748b'],
    ];

    public const EVENT_STATUSES = [
        ['name' => 'Angefragt', 'color' => '#f59e0b', 'order' => 1],
        ['name' => 'Optioniert', 'color' => '#2563eb', 'order' => 2],
        ['name' => 'Bestätigt', 'color' => '#059669', 'order' => 3, 'default' => true],
        ['name' => 'Abgesagt', 'color' => '#ef4444', 'order' => 4],
    ];

    public const PROJECT_STATES = [
        ['name' => 'Idee', 'color' => '#94a3b8', 'is_planning' => true],
        ['name' => 'In Planung', 'color' => '#2563eb', 'is_planning' => true],
        ['name' => 'Bestätigt', 'color' => '#059669', 'is_planning' => false],
        ['name' => 'Läuft', 'color' => '#7c3aed', 'is_planning' => false],
        ['name' => 'Abgeschlossen', 'color' => '#166534', 'is_planning' => false],
        ['name' => 'Abgesagt', 'color' => '#ef4444', 'is_planning' => false],
    ];

    public const CATEGORIES = ['Eigenproduktion', 'Gastspiel', 'Festival', 'Vermietung', 'Konzert'];
    public const GENRES = ['Tanz', 'Schauspiel', 'Konzert', 'Performance', 'Musiktheater', 'Lesung'];
    public const SECTORS = ['Große Bühne', 'Studiobühne', 'Outreach & Vermittlung'];

    public const COST_CENTERS = [
        ['name' => '4711 – Künstlerisches Programm'],
        ['name' => '4712 – Gastspiele'],
        ['name' => '4720 – Vermietung & Events'],
        ['name' => '4730 – Festival'],
    ];

    public const DEPARTMENTS = [
        ['name' => 'Technik', 'svg_name' => 'icon_technik_buehne'],
        ['name' => 'Produktion', 'svg_name' => 'icon_orga_finanzen'],
        ['name' => 'Kommunikation', 'svg_name' => 'icon_bildung_kulturell'],
    ];

    /* -----------------------------------------------------------------
     | Personen
     | -----------------------------------------------------------------
     | craft: Schlüssel aus CRAFTS (primäres Gewerk, null = kein Schichtdienst)
     | qualification: Schlüssel aus QUALIFICATIONS für das primäre Gewerk
     | contract: Schlüssel aus CONTRACTS; pattern: Schlüssel aus WORK_TIME_PATTERNS
     | admin/planner: Rechte-Verteilung (planner = Schichtplaner des Gewerks)
     */

    public const USERS = [
        // Leitung & Verwaltung (kein Schichtdienst)
        ['first' => 'Katrin', 'last' => 'Vollmer', 'pronouns' => 'sie/ihr', 'position' => 'Künstlerische Leitung',
            'craft' => null, 'admin' => true, 'contract' => 'haustarif', 'pattern' => 'verwaltung'],
        ['first' => 'Jonas', 'last' => 'Brandt', 'pronouns' => 'er/ihm', 'position' => 'Technischer Direktor',
            'craft' => null, 'admin' => true, 'contract' => 'haustarif', 'pattern' => 'verwaltung'],
        ['first' => 'Miriam', 'last' => 'Petersen', 'pronouns' => 'sie/ihr', 'position' => 'Produktionsleitung',
            'craft' => null, 'contract' => 'haustarif', 'pattern' => 'verwaltung'],
        ['first' => 'Deniz', 'last' => 'Aydın', 'pronouns' => 'dey/deren', 'position' => 'Produktionsleitung',
            'craft' => null, 'contract' => 'haustarif', 'pattern' => 'verwaltung'],
        ['first' => 'Helga', 'last' => 'Storm', 'pronouns' => 'sie/ihr', 'position' => 'Verwaltungsleitung',
            'craft' => null, 'contract' => 'haustarif', 'pattern' => 'verwaltung'],
        ['first' => 'Tobias', 'last' => 'Krause', 'pronouns' => 'er/ihm', 'position' => 'Marketing & Presse',
            'craft' => null, 'contract' => 'teilzeit', 'pattern' => 'teilzeit'],
        ['first' => 'Sandra', 'last' => 'Wilkens', 'pronouns' => 'sie/ihr', 'position' => 'Buchhaltung',
            'craft' => null, 'contract' => 'teilzeit', 'pattern' => 'teilzeit'],
        ['first' => 'Ayşe', 'last' => 'Demir', 'pronouns' => 'sie/ihr', 'position' => 'Assistenz der Leitung',
            'craft' => null, 'contract' => 'haustarif', 'pattern' => 'verwaltung'],

        // Gewerke-Leitungen (= Schichtplaner ihres Gewerks)
        ['first' => 'Frank', 'last' => 'Ohlsen', 'pronouns' => 'er/ihm', 'position' => 'Leitung Bühnentechnik',
            'craft' => 'buehne', 'qualification' => 'meister', 'planner' => true,
            'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Petra', 'last' => 'Lindholm', 'pronouns' => 'sie/ihr', 'position' => 'Beleuchtungsmeisterin',
            'craft' => 'licht', 'qualification' => 'meister', 'planner' => true,
            'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Marek', 'last' => 'Nowak', 'pronouns' => 'er/ihm', 'position' => 'Tonmeister',
            'craft' => 'ton', 'qualification' => 'meister', 'planner' => true,
            'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Svenja', 'last' => 'Carstens', 'pronouns' => 'sie/ihr', 'position' => 'Leitung Einlass & Service',
            'craft' => 'einlass', 'qualification' => 'vorarbeiter', 'planner' => true,
            'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],

        // Bühne
        ['first' => 'Ole', 'last' => 'Jensen', 'pronouns' => 'er/ihm', 'position' => 'Bühnenhandwerker',
            'craft' => 'buehne', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Rico', 'last' => 'Albrecht', 'pronouns' => 'er/ihm', 'position' => 'Bühnentechniker',
            'craft' => 'buehne', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Tarik', 'last' => 'Yılmaz', 'pronouns' => 'er/ihm', 'position' => 'Bühnentechniker',
            'craft' => 'buehne', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Jens', 'last' => 'Peters', 'pronouns' => 'er/ihm', 'position' => 'Seitenmeister',
            'craft' => 'buehne', 'qualification' => 'vorarbeiter', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],

        // Licht
        ['first' => 'Lea', 'last' => 'Winkler', 'pronouns' => 'sie/ihr', 'position' => 'Lichttechnikerin / Operatorin',
            'craft' => 'licht', 'qualification' => 'operator', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Samuel', 'last' => 'Osei', 'pronouns' => 'er/ihm', 'position' => 'Lichttechniker',
            'craft' => 'licht', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Nina', 'last' => 'Hartwig', 'pronouns' => 'sie/ihr', 'position' => 'Lichttechnikerin',
            'craft' => 'licht', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],

        // Ton
        ['first' => 'Aylin', 'last' => 'Şahin', 'pronouns' => 'sie/ihr', 'position' => 'Tontechnikerin',
            'craft' => 'ton', 'qualification' => 'operator', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'David', 'last' => 'Kern', 'pronouns' => 'er/ihm', 'position' => 'Tontechniker',
            'craft' => 'ton', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],

        // Video
        ['first' => 'Greta', 'last' => 'Paulsen', 'pronouns' => 'sie/ihr', 'position' => 'Videotechnikerin',
            'craft' => 'video', 'qualification' => 'operator', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Milan', 'last' => 'Petrović', 'pronouns' => 'er/ihm', 'position' => 'Videotechniker',
            'craft' => 'video', 'contract' => 'teilzeit', 'pattern' => 'teilzeit'],

        // Kostüm
        ['first' => 'Ruth', 'last' => 'Blankenburg', 'pronouns' => 'sie/ihr', 'position' => 'Gewandmeisterin',
            'craft' => 'kostuem', 'qualification' => 'meister', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Hannah', 'last' => 'Vogt', 'pronouns' => 'sie/ihr', 'position' => 'Ankleiderin',
            'craft' => 'kostuem', 'contract' => 'teilzeit', 'pattern' => 'teilzeit'],

        // Maske
        ['first' => 'Iris', 'last' => 'Falk', 'pronouns' => 'sie/ihr', 'position' => 'Chefmaskenbildnerin',
            'craft' => 'maske', 'qualification' => 'meister', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Selin', 'last' => 'Kaya', 'pronouns' => 'sie/ihr', 'position' => 'Maskenbildnerin',
            'craft' => 'maske', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],

        // Requisite
        ['first' => 'Bernd', 'last' => 'Otte', 'pronouns' => 'er/ihm', 'position' => 'Requisiteur',
            'craft' => 'requisite', 'qualification' => 'vorarbeiter', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Charlotte', 'last' => 'Weiß', 'pronouns' => 'sie/ihr', 'position' => 'Requisiteurin',
            'craft' => 'requisite', 'contract' => 'teilzeit', 'pattern' => 'teilzeit'],

        // Einlass & Service
        ['first' => 'Emre', 'last' => 'Polat', 'pronouns' => 'er/ihm', 'position' => 'Einlass & Abendkasse',
            'craft' => 'einlass', 'contract' => 'teilzeit', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Franziska', 'last' => 'Lorenz', 'pronouns' => 'sie/ihr', 'position' => 'Einlass & Garderobe',
            'craft' => 'einlass', 'contract' => 'teilzeit', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Gustav', 'last' => 'Meinert', 'pronouns' => 'er/ihm', 'position' => 'Einlass & Service',
            'craft' => 'einlass', 'contract' => 'teilzeit', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Ida', 'last' => 'Johannsen', 'pronouns' => 'sie/ihr', 'position' => 'Einlass & Service',
            'craft' => 'einlass', 'contract' => 'teilzeit', 'pattern' => 'theaterbetrieb'],

        // Auszubildende (universelles Gewerk — in allen Gewerken einsetzbar)
        ['first' => 'Tim', 'last' => 'Bergmann', 'pronouns' => 'er/ihm', 'position' => 'Azubi Veranstaltungstechnik (1. Jahr)',
            'craft' => 'azubi', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Zoe', 'last' => 'Krämer', 'pronouns' => 'sie/ihr', 'position' => 'Azubi Veranstaltungstechnik (2. Jahr)',
            'craft' => 'azubi', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Luca', 'last' => 'Moretti', 'pronouns' => 'dey/deren', 'position' => 'Azubi Veranstaltungstechnik (3. Jahr)',
            'craft' => 'azubi', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Finja', 'last' => 'Dreyer', 'pronouns' => 'sie/ihr', 'position' => 'Azubi Fachkraft Veranstaltungstechnik',
            'craft' => 'azubi', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],

        // Allrounder (universelles Gewerk VT)
        ['first' => 'Viktor', 'last' => 'Hansen', 'pronouns' => 'er/ihm', 'position' => 'Veranstaltungstechniker (Allrounder)',
            'craft' => 'vt', 'qualification' => 'meister', 'contract' => 'nv_buehne', 'pattern' => 'theaterbetrieb'],
        ['first' => 'Maren', 'last' => 'Kolbe', 'pronouns' => 'sie/ihr', 'position' => 'Veranstaltungstechnikerin (Allrounderin)',
            'craft' => 'vt', 'contract' => 'haustarif', 'pattern' => 'theaterbetrieb'],
    ];

    public const FREELANCERS = [
        ['first' => 'Kai', 'last' => 'Brenner', 'position' => 'Lichtdesigner', 'craft' => 'licht',
            'qualification' => 'operator', 'salary' => 38],
        ['first' => 'Yuki', 'last' => 'Tanaka', 'position' => 'VJ / Video-Operator', 'craft' => 'video',
            'qualification' => 'operator', 'salary' => 42],
        ['first' => 'Rosa', 'last' => 'Vidal', 'position' => 'Tontechnikerin FOH', 'craft' => 'ton',
            'qualification' => 'operator', 'salary' => 40],
        ['first' => 'Sergej', 'last' => 'Baum', 'position' => 'Rigger (SQQ2)', 'craft' => 'buehne',
            'qualification' => 'meister', 'salary' => 45],
        ['first' => 'Anette', 'last' => 'Cornelius', 'position' => 'Maskenbildnerin', 'craft' => 'maske',
            'qualification' => 'mitarbeiter', 'salary' => 32],
        ['first' => 'Leon', 'last' => 'Fricke', 'position' => 'Stagehand', 'craft' => 'buehne',
            'qualification' => 'mitarbeiter', 'salary' => 24],
        ['first' => 'Carla', 'last' => 'Johannson', 'position' => 'Kostümbildnerin', 'craft' => 'kostuem',
            'qualification' => 'mitarbeiter', 'salary' => 34],
        ['first' => 'Ben', 'last' => 'Storjohann', 'position' => 'Stagehand', 'craft' => 'buehne',
            'qualification' => 'mitarbeiter', 'salary' => 24],
    ];

    public const SERVICE_PROVIDERS = [
        ['name' => 'SecuTeam Nord GmbH', 'type' => 'work', 'craft' => 'einlass',
            'email' => 'dispo@secuteam-nord.example', 'phone' => '+49 40 555 011 22',
            'note' => 'Einlass- und Sicherheitsdienst, Abrufkontingent lt. Rahmenvertrag.'],
        ['name' => 'Rent-a-Rig Veranstaltungstechnik', 'type' => 'work', 'craft' => 'buehne',
            'email' => 'crew@rent-a-rig.example', 'phone' => '+49 40 555 033 44',
            'note' => 'Rigging- und Stagehand-Crews inkl. Material.'],
        ['name' => 'Pyro & FX Hamburg', 'type' => 'work', 'craft' => 'buehne',
            'email' => 'kontakt@pyro-fx.example', 'phone' => '+49 40 555 077 88',
            'note' => 'Pyrotechnik und Spezialeffekte, nur mit BE-Schein-Personal.'],
        ['name' => 'Gebäudereinigung Elbblick', 'type' => 'work', 'craft' => null,
            'email' => 'service@elbblick-reinigung.example', 'phone' => '+49 40 555 099 00',
            'note' => 'Unterhaltsreinigung, Sonderreinigung nach Veranstaltungen.'],
        ['name' => 'Catering Speisekammer', 'type' => 'work', 'craft' => null,
            'email' => 'events@speisekammer.example', 'phone' => '+49 40 555 066 77',
            'note' => 'Premierenfeiern, Künstler*innen-Catering.'],
    ];

    /* -----------------------------------------------------------------
     | Texte
     | ----------------------------------------------------------------- */

    public const SHIFT_DESCRIPTIONS = [
        'Treffpunkt Bühneneingang, Funk Kanal 2.',
        'Fokus Licht Gasse 3, danach Einleuchten Zyklorama.',
        'Einlass inkl. Abendkasse und Garderobe.',
        'Umbau nach Szene 4, Details siehe Umbauplan.',
        'Anlieferung über Rampe Hof, Stapler bereitstellen.',
        'Soundcheck ab 16:00, FOH besetzt.',
        'Requisiten-Check vor Einlass, Übergabe an Abendspielleitung.',
        'Maske ab 2 Stunden vor Vorstellungsbeginn.',
    ];

    public const SHIFT_WORKER_NOTES = [
        ['start_offset' => 60, 'note' => 'Kommt später – Anlieferung Werkstatt.'],
        ['start_offset' => 30, 'note' => 'Kommt 30 Min. später – Übergabe Probebühne.'],
        ['end_offset' => -60, 'note' => 'Geht früher – Anschlusstermin im Haus.'],
        ['end_offset' => -30, 'note' => 'Geht 22:00 – Kinderbetreuung.'],
        ['start_offset' => -30, 'note' => 'Kommt früher – Einweisung neue Azubis.'],
    ];

    public const DECLINE_COMMENTS = [
        'Bin an dem Tag beim Zahnarzt.',
        'Familienfeier, leider verhindert.',
        'Bereits privat verplant – gerne die Woche drauf.',
    ];

    /** Deterministische Personalnummern-Basis etc. können hier später ergänzt werden. */
    public static function email(string $first, string $last): string
    {
        $slug = static fn (string $value): string => str_replace(
            ['ä', 'ö', 'ü', 'ß', 'ş', 'ç', 'ğ', 'ı', 'é', 'è', 'ć', 'ž', 'ó'],
            ['ae', 'oe', 'ue', 'ss', 's', 'c', 'g', 'i', 'e', 'e', 'c', 'z', 'o'],
            mb_strtolower($value)
        );
        $local = preg_replace('/[^a-z0-9.]/', '', $slug($first) . '.' . $slug($last));

        return $local . '@' . self::EMAIL_DOMAIN;
    }
}
