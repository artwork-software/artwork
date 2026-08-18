<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * Kuratierte Pools für Projekte, Termin-Templates und Schicht-Matrizen.
 */
final class DemoProjectPools
{
    /**
     * Projekt-Archetypen. events werden vom DemoProjectSeeder relativ zum
     * "Ankertag" (Premiere bzw. Haupttag) erzeugt.
     */
    public const ARCHETYPES = [
        'eigenproduktion' => [
            'per_month' => [2, 2],
            'category' => 'Eigenproduktion',
            'sector' => 'Große Bühne',
            'cost_center' => '4711 – Künstlerisches Programm',
            'stage_role' => 'main_stage',
            'color' => '#9e1c60',
        ],
        'gastspiel' => [
            'per_month' => [2, 3],
            'category' => 'Gastspiel',
            'sector' => 'Große Bühne',
            'cost_center' => '4712 – Gastspiele',
            'stage_role' => 'second_stage',
            'color' => '#2563eb',
        ],
        'konzert' => [
            'per_month' => [2, 3],
            'category' => 'Konzert',
            'sector' => 'Studiobühne',
            'cost_center' => '4711 – Künstlerisches Programm',
            'stage_role' => 'foyer',
            'color' => '#059669',
        ],
        'vermietung' => [
            'per_month' => [1, 2],
            'category' => 'Vermietung',
            'sector' => 'Studiobühne',
            'cost_center' => '4720 – Vermietung & Events',
            'stage_role' => 'second_stage',
            'color' => '#b45309',
        ],
    ];

    /** name, genre, artists — pro Archetyp; Auswahl deterministisch je Monat/Slot. */
    public const PROJECT_POOLS = [
        'eigenproduktion' => [
            ['name' => 'Winterreise – ein Tanzabend', 'genre' => 'Tanz', 'artists' => 'Compagnie Testhaus, Choreografie: Mara Ilić'],
            ['name' => 'Kaspar Häuser Meer', 'genre' => 'Schauspiel', 'artists' => 'Ensemble Testhaus, Regie: Jule Brandes'],
            ['name' => 'Die Verwandlung', 'genre' => 'Schauspiel', 'artists' => 'Ensemble Testhaus, Regie: Omar Khaled'],
            ['name' => 'Atlas der abgelegenen Inseln', 'genre' => 'Performance', 'artists' => 'Kollektiv Fernweh'],
            ['name' => 'Momo und die Zeitdiebe', 'genre' => 'Schauspiel', 'artists' => 'Junges Testhaus, Regie: Sina Albers'],
            ['name' => 'Woyzeck Fragmente', 'genre' => 'Musiktheater', 'artists' => 'Ensemble Testhaus & Bandkollektiv Marotte'],
            ['name' => 'Peer Gynt', 'genre' => 'Schauspiel', 'artists' => 'Ensemble Testhaus, Regie: Henrik Dall'],
            ['name' => 'Antigone Reloaded', 'genre' => 'Performance', 'artists' => 'Kollektiv Beton & Gäste'],
        ],
        'gastspiel' => [
            ['name' => 'Gastspiel: Compagnie Marelle – »Traces«', 'genre' => 'Tanz', 'artists' => 'Compagnie Marelle (Lyon)'],
            ['name' => 'Gastspiel: Ensemble Nordwind – »Brandung«', 'genre' => 'Schauspiel', 'artists' => 'Ensemble Nordwind (Kiel)'],
            ['name' => 'Gastspiel: Teatro Luna – »La Casa«', 'genre' => 'Performance', 'artists' => 'Teatro Luna (Sevilla)'],
            ['name' => 'Gastspiel: Duo Fragile – Zirkusabend', 'genre' => 'Performance', 'artists' => 'Duo Fragile (Montréal)'],
            ['name' => 'Gastspiel: Tanzkollektiv PULS – »Echo«', 'genre' => 'Tanz', 'artists' => 'Tanzkollektiv PULS (Wien)'],
            ['name' => 'Gastspiel: Figurentheater Wolkenschieber', 'genre' => 'Schauspiel', 'artists' => 'Figurentheater Wolkenschieber (Erfurt)'],
            ['name' => 'Gastspiel: Studio Nachtblau – »Fluten«', 'genre' => 'Performance', 'artists' => 'Studio Nachtblau (Zürich)'],
            ['name' => 'Gastspiel: Compagnia Vento – »Aria«', 'genre' => 'Tanz', 'artists' => 'Compagnia Vento (Turin)'],
        ],
        'konzert' => [
            ['name' => 'Nachtschicht: Elektro trifft Orchester', 'genre' => 'Konzert', 'artists' => 'DJ Marlow & Kammerorchester Elbe'],
            ['name' => 'Jazz im Foyer: Trio Anders', 'genre' => 'Konzert', 'artists' => 'Trio Anders'],
            ['name' => 'Liederabend: Von fernen Ufern', 'genre' => 'Konzert', 'artists' => 'Selma Kron (Mezzosopran), Pavel Orlov (Klavier)'],
            ['name' => 'Klangkosmos: Minimal Music', 'genre' => 'Konzert', 'artists' => 'Ensemble Repeat'],
            ['name' => 'Singer-Songwriter-Slam', 'genre' => 'Konzert', 'artists' => 'Diverse, Moderation: Nele Fuchs'],
            ['name' => 'Lesung & Musik: Nachtgestalten', 'genre' => 'Lesung', 'artists' => 'Jasper Thiel (Text), Ada Lund (Cello)'],
        ],
        'vermietung' => [
            ['name' => 'Firmenevent Hanseatic Insurance', 'genre' => 'Konzert', 'artists' => 'Extern: Hanseatic Insurance AG'],
            ['name' => 'Jahrestagung Logistikverband Nord', 'genre' => 'Lesung', 'artists' => 'Extern: Logistikverband Nord e.V.'],
            ['name' => 'Absolvent*innenfeier HAW', 'genre' => 'Konzert', 'artists' => 'Extern: HAW Hamburg'],
            ['name' => 'Charity-Gala Kinderhospiz', 'genre' => 'Konzert', 'artists' => 'Extern: Stiftung Kinderhospiz'],
            ['name' => 'Produktpräsentation Nordlicht Mobility', 'genre' => 'Performance', 'artists' => 'Extern: Nordlicht Mobility GmbH'],
        ],
    ];

    public const FESTIVAL = [
        'group_name' => 'Testhaus Festival %s',
        'category' => 'Festival',
        'cost_center' => '4730 – Festival',
        'color' => '#7c3aed',
        'artists' => 'Diverse internationale Compagnien',
        'sub_projects' => [
            ['name' => 'Festival: Eröffnung & Empfang %s', 'genre' => 'Performance', 'artists' => 'Alle Festival-Compagnien', 'stage_role' => 'foyer'],
            ['name' => 'Festival: Tanz-Triple %s', 'genre' => 'Tanz', 'artists' => 'Cie. Marelle / PULS / Compagnia Vento', 'stage_role' => 'main_stage'],
            ['name' => 'Festival: Lange Nacht der Performance %s', 'genre' => 'Performance', 'artists' => 'Kollektiv Beton, Studio Nachtblau u.a.', 'stage_role' => 'second_stage'],
        ],
    ];

    /* -----------------------------------------------------------------
     | Raum-Auslastung: Hausprogramm-Reihen, die die Lücken ALLER Räume
     | (auch der vorbestehenden) mit realitätsnahen Formaten füllen.
     | ----------------------------------------------------------------- */

    public const FILL_SERIES = [
        'offene_buehne' => [
            'project' => 'Offene Bühne & Hausprogramm',
            'artists' => 'Diverse Künstler*innen der freien Szene',
            'color' => '#0891b2',
        ],
        'workshops' => [
            'project' => 'Theaterpädagogik & Workshops',
            'artists' => 'Vermittlungsteam Testhaus & Gäste',
            'color' => '#059669',
        ],
        'gastproben' => [
            'project' => 'Gastproben & Residenzen',
            'artists' => 'Wechselnde Compagnien',
            'color' => '#7c3aed',
        ],
        'vermietungen' => [
            'project' => 'Externe Nutzungen & Vermietungen',
            'artists' => 'Externe Veranstalter*innen',
            'color' => '#b45309',
        ],
    ];

    /**
     * Formate fürs Auffüllen: [seriesKey, eventTypeKey, name, startzeit, endzeit, tageszeit]
     * tageszeit: day|evening — steuert, welche Slots belegt werden.
     */
    public const FILL_FORMATS = [
        ['workshops', 'sonderveranstaltung', 'Workshop: Bühnenkampf & Falltechnik', '10:00', '15:00', 'day'],
        ['workshops', 'sonderveranstaltung', 'Workshop: Licht für Einsteiger*innen', '10:00', '16:00', 'day'],
        ['workshops', 'probe', 'Jugendclub-Probe', '16:00', '19:00', 'day'],
        ['gastproben', 'probe', 'Gastprobe: Compagnie Fremdkörper', '10:00', '17:00', 'day'],
        ['gastproben', 'probe', 'Residenz: Kollektiv Windstärke', '10:00', '18:00', 'day'],
        ['offene_buehne', 'vorstellung', 'Offene Bühne', '19:30', '22:30', 'evening'],
        ['offene_buehne', 'vorstellung', 'Kindertheater am Nachmittag', '15:00', '16:30', 'day'],
        ['offene_buehne', 'sonderveranstaltung', 'Poetry Slam', '20:00', '22:30', 'evening'],
        ['vermietungen', 'sonderveranstaltung', 'Firmenveranstaltung (Vermietung)', '18:00', '22:30', 'evening'],
        ['vermietungen', 'sonderveranstaltung', 'Empfang & Netzwerkabend (Vermietung)', '18:30', '22:00', 'evening'],
        ['vermietungen', 'fuehrung', 'Führung hinter die Kulissen', '11:00', '12:00', 'day'],
    ];

    public const PLANNING_PROJECTS = [
        ['name' => 'Spielzeit-Planung %s: Ideenskizzen', 'genre' => 'Schauspiel', 'artists' => 'Künstlerische Leitung'],
        ['name' => 'Kooperation Jugendclub (in Planung)', 'genre' => 'Performance', 'artists' => 'Junges Testhaus'],
        ['name' => 'Open-Air-Reihe Vorplatz (Konzept)', 'genre' => 'Konzert', 'artists' => 'N.N.'],
    ];

    /* -----------------------------------------------------------------
     | Schicht-Matrizen: je Termintyp-Schlüssel die benötigten Funktionen
     | pro Gewerk: [craftKey => [qualificationKey => Anzahl]]
     | ----------------------------------------------------------------- */

    public const SHIFT_MATRICES = [
        'vorstellung' => [
            'buehne' => ['mitarbeiter' => 2, 'meister' => 1],
            'licht' => ['mitarbeiter' => 1, 'operator' => 1],
            'ton' => ['mitarbeiter' => 1],
            'einlass' => ['mitarbeiter' => 3, 'vorarbeiter' => 1],
        ],
        'vorstellung_gross' => [
            'buehne' => ['mitarbeiter' => 3, 'meister' => 1],
            'licht' => ['mitarbeiter' => 1, 'operator' => 1],
            'ton' => ['mitarbeiter' => 1, 'operator' => 1],
            'video' => ['mitarbeiter' => 1],
            'kostuem' => ['mitarbeiter' => 1],
            'maske' => ['mitarbeiter' => 1],
            'einlass' => ['mitarbeiter' => 4, 'vorarbeiter' => 1],
        ],
        'generalprobe' => [
            'buehne' => ['mitarbeiter' => 2, 'meister' => 1],
            'licht' => ['mitarbeiter' => 1, 'operator' => 1],
            'ton' => ['mitarbeiter' => 1],
            'kostuem' => ['mitarbeiter' => 1],
            'maske' => ['mitarbeiter' => 1],
        ],
        'endprobe' => [
            'licht' => ['mitarbeiter' => 1],
            'ton' => ['mitarbeiter' => 1],
        ],
        'aufbau' => [
            'buehne' => ['mitarbeiter' => 4, 'meister' => 1],
            'licht' => ['mitarbeiter' => 2],
            'ton' => ['mitarbeiter' => 1],
            'video' => ['mitarbeiter' => 1],
            'requisite' => ['mitarbeiter' => 1],
        ],
        'abbau' => [
            'buehne' => ['mitarbeiter' => 3, 'meister' => 1],
            'licht' => ['mitarbeiter' => 1],
            'ton' => ['mitarbeiter' => 1],
        ],
        'sonderveranstaltung' => [
            'buehne' => ['mitarbeiter' => 1],
            'ton' => ['mitarbeiter' => 1],
            'einlass' => ['mitarbeiter' => 2, 'vorarbeiter' => 1],
        ],
    ];

    /** Schichtzeiten je Matrix-Schlüssel: [start, ende, pause_min, tag_offset_ende] */
    public const SHIFT_TIMES = [
        'vorstellung' => ['17:00', '23:00', 30, 0],
        'vorstellung_gross' => ['17:00', '23:00', 30, 0],
        'generalprobe' => ['16:00', '22:00', 30, 0],
        'endprobe' => ['17:00', '22:00', 30, 0],
        'aufbau' => ['09:00', '18:00', 60, 0],
        'abbau' => ['22:00', '02:00', 0, 1],
        'sonderveranstaltung' => ['16:00', '23:30', 30, 0],
    ];

    /** Einlass-Schichten weichen zeitlich ab (Dienstbeginn vor Einlass). */
    public const FRONT_OF_HOUSE_TIMES = ['18:00', '22:30', 0, 0];

    /* -----------------------------------------------------------------
     | Tab-Inhalte
     | ----------------------------------------------------------------- */

    public const CHECKLIST_TEMPLATES = [
        'eigenproduktion' => [
            'name' => 'Premierencheckliste',
            'tasks' => [
                'Endprobenplan an alle Gewerke verschickt',
                'Feuerwehr-Abnahme Bühnenbild terminiert',
                'Programmheft in Druck gegeben',
                'Premierenfeier organisiert (Catering, Foyer)',
                'Pressefotos bei der Generalprobe eingeplant',
                'Übertitel / Barrierefreiheit geprüft',
            ],
        ],
        'gastspiel' => [
            'name' => 'Gastspiel-Checkliste',
            'tasks' => [
                'Technischer Rider geprüft und beantwortet',
                'Hotelzimmer für Compagnie gebucht',
                'Transport / Anlieferung terminiert',
                'Gastspielvertrag unterschrieben zurück',
                'Abendgagen-Abrechnung vorbereitet',
            ],
        ],
        'konzert' => [
            'name' => 'Konzert-Checkliste',
            'tasks' => [
                'Backline-Bedarf geklärt',
                'GEMA-Meldung vorbereitet',
                'Einlass- und Bestuhlungsplan abgestimmt',
                'Merch-Tisch im Foyer eingeplant',
            ],
        ],
        'vermietung' => [
            'name' => 'Vermietungs-Checkliste',
            'tasks' => [
                'Mietvertrag unterschrieben',
                'Technikbedarf mit Kunde abgestimmt',
                'Personalbedarf Einlass & Garderobe geplant',
                'Schlussrechnung vorbereitet',
            ],
        ],
    ];

    public const COMMENTS = [
        'Bühnenbild-Entwurf ist freigegeben, Werkstätten beginnen nächste Woche.',
        'Achtung: Anlieferung verschiebt sich auf den Nachmittag, Rampe ist vormittags blockiert.',
        'Rider der Compagnie ist da – Video braucht zusätzlichen Projektor, kläre Leihgerät.',
        'Presse hat für die Premiere zwei Plätze reserviert, bitte in der Abendkasse hinterlegen.',
        'Kostümanproben laufen, zweite Anprobe am Donnerstag.',
        'Budget für Marketing um 500 € erhöht, siehe Budget-Tab.',
        'Foyer-Bar übernimmt die Pausenversorgung, Aufbau ab 17 Uhr.',
        'Nachbesprechung mit dem Team am Montag 10 Uhr auf Probebühne 2.',
    ];

    public const TECH_REQUIREMENTS = [
        'eigenproduktion' => "Drehbühne wird genutzt, Einrichtung ab Aufbautag 1.\nZusätzlich 12 Moving Lights aus dem Lager, Haze durchgehend.\nAchtung: offene Flamme in Szene 3 – Brandwache erforderlich.",
        'gastspiel' => "Rider siehe Dokumente. Compagnie bringt eigenes Licht-Pult mit (MA3).\nTon: 2x d&b Monitor zusätzlich, FOH ab Soundcheck besetzt.\nTanzboden schwarz, Verlegung am Aufbautag.",
        'konzert' => "Backline: Flügel (gestimmt), Drum-Riser 3x2m.\nFOH + Monitor aus dem Haus, 4 Wege In-Ear für Band.\nFoyer-Bestuhlung: Clubtische, 120 Plätze.",
        'vermietung' => "Standard-Konferenz-Setup: Rednerpult, 2x Handfunk, Beamer 12k.\nEinlass ab 60 Minuten vor Beginn, Garderobe besetzt.\nAufbau durch Kunde ab 14 Uhr, Hausbetreuung durch VT.",
    ];

    public const DAY_REMARKS = [
        'Premiere! Alle Gewerke ab 16 Uhr im Haus, Sicherheitsrundgang 17:30.',
        'Festival-Eröffnung – Foyer ab 15 Uhr für Aufbau gesperrt.',
        'Trafo-Wartung im Haupthaus 8–10 Uhr, kurzzeitig kein Strom auf der Großen Bühne.',
        'Betriebsversammlung 9 Uhr im Ballettsaal, Werkstätten geschlossen.',
    ];

    /** Wochentage (ISO) für Vorstellungen: Do–So bevorzugt. */
    public const SHOW_WEEKDAYS = [4, 5, 6, 7];

    public const EVENT_NAME_SHOW = ['Premiere', '2. Vorstellung', '3. Vorstellung', '4. Vorstellung',
        '5. Vorstellung', '6. Vorstellung', '7. Vorstellung', 'Derniere'];

    /**
     * Archetyp eines (Demo-)Projekts anhand des Namens — deckt auch
     * "… (Wiederaufnahme)"-Varianten und die Festival-Namensmuster ab.
     * null = kein Demo-Projekt.
     */
    public static function archetypeForProjectName(string $name): ?string
    {
        foreach (self::PROJECT_POOLS as $archetypeKey => $pool) {
            foreach ($pool as $entry) {
                if ($name === $entry['name'] || str_starts_with($name, $entry['name'] . ' (')) {
                    return $archetypeKey;
                }
            }
        }

        $festivalPrefixes = array_map(
            static fn (array $sub) => explode('%s', $sub['name'])[0],
            self::FESTIVAL['sub_projects']
        );
        $festivalPrefixes[] = explode('%s', self::FESTIVAL['group_name'])[0];
        foreach ($festivalPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return 'eigenproduktion';
            }
        }

        foreach (self::FILL_SERIES as $series) {
            if ($name === $series['project']) {
                return 'hausnutzung';
            }
        }

        return null;
    }
}
