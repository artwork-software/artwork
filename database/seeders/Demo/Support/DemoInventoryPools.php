<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * Kuratierte Pools für das Inventar des Artwork Testhauses: Eigenschaften,
 * Kategorie-Zuordnungen und Artikel. Artikelnamen der ersten Seeder-Runde
 * bleiben erhalten, damit firstOrCreate Bestandsdaten wiederfindet.
 */
final class DemoInventoryPools
{
    /**
     * Eigenschafts-Definitionen (Name => Attribute). Namen sind der
     * Idempotenz-Schlüssel; "Raum" und "Hersteller" existieren als
     * Systemeigenschaften bereits und werden hier nicht definiert.
     */
    public const PROPERTIES = [
        'Zustand' => [
            'type' => 'selection',
            'select_values' => ['Neuwertig', 'Gut', 'Gebrauchsspuren', 'Reparaturbedürftig'],
            'show_in_list' => true,
            'is_filterable' => true,
            'individual_value' => true,
            'tooltip_text' => 'Allgemeiner Erhaltungszustand; bei Einzelbestand je Exemplar gepflegt.',
        ],
        'Seriennummer' => [
            'type' => 'text',
            'individual_value' => true,
            'tooltip_text' => 'Herstellerseitige Seriennummer des Einzelgeräts.',
        ],
        'Anschaffungsjahr' => [
            'type' => 'year',
            'across_articles' => true,
        ],
        'Nächste Prüfung' => [
            'type' => 'date',
            'show_in_list' => true,
            'individual_value' => true,
            'tooltip_text' => 'Fälligkeit der nächsten DGUV-V3-/Sachkundeprüfung.',
        ],
        'Leistung (W)' => [
            'type' => 'number',
            'is_filterable' => true,
        ],
        'Stromanschluss' => [
            'type' => 'selection',
            'select_values' => ['Schuko', 'powerCON', 'powerCON TRUE1', 'CEE16', 'CEE32', 'Batterie/Akku'],
            'is_filterable' => true,
        ],
        'Gewicht (kg)' => [
            'type' => 'number',
        ],
        'Traglast (kg)' => [
            'type' => 'number',
            'show_in_list' => true,
        ],
        'Länge (m)' => [
            'type' => 'number',
            'is_filterable' => true,
        ],
        'Material' => [
            'type' => 'selection',
            'select_values' => ['Holz', 'Metall', 'Kunststoff', 'Stoff', 'Leder', 'Glas', 'Papier/Karton'],
        ],
        'Größe' => [
            'type' => 'selection',
            'select_values' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Universal'],
            'show_in_list' => true,
            'is_filterable' => true,
        ],
        'Farbe' => [
            'type' => 'text',
            'show_in_list' => true,
        ],
        'Verleihbar' => [
            'type' => 'checkbox',
            'show_in_list' => true,
            'is_filterable' => true,
            'tooltip_text' => 'Darf im Rahmen externer Ausgaben verliehen werden.',
        ],
    ];

    /**
     * Kategorie => Eigenschaftsnamen in Anzeige-Reihenfolge (Pivot-Position).
     * "Hersteller" und "Raum" referenzieren die Systemeigenschaften.
     */
    public const CATEGORY_PROPERTIES = [
        'Licht' => [
            'Hersteller', 'Raum', 'Zustand', 'Leistung (W)', 'Stromanschluss',
            'Gewicht (kg)', 'Anschaffungsjahr', 'Nächste Prüfung', 'Seriennummer', 'Verleihbar',
        ],
        'Ton' => [
            'Hersteller', 'Raum', 'Zustand', 'Gewicht (kg)', 'Anschaffungsjahr',
            'Nächste Prüfung', 'Seriennummer', 'Verleihbar',
        ],
        'Video' => [
            'Hersteller', 'Raum', 'Zustand', 'Leistung (W)', 'Anschaffungsjahr',
            'Nächste Prüfung', 'Seriennummer', 'Verleihbar',
        ],
        'Bühne & Rigging' => [
            'Hersteller', 'Raum', 'Zustand', 'Material', 'Gewicht (kg)',
            'Traglast (kg)', 'Anschaffungsjahr', 'Nächste Prüfung', 'Verleihbar',
        ],
        'Kostüm & Requisite' => [
            'Raum', 'Zustand', 'Größe', 'Farbe', 'Material', 'Anschaffungsjahr', 'Verleihbar',
        ],
        'Verbrauchsmaterial' => [
            'Hersteller', 'Raum', 'Länge (m)', 'Farbe',
        ],
        'Transport & Cases' => [
            'Hersteller', 'Raum', 'Zustand', 'Material', 'Gewicht (kg)',
            'Traglast (kg)', 'Anschaffungsjahr', 'Verleihbar',
        ],
        'Werkstatt & Werkzeug' => [
            'Hersteller', 'Raum', 'Zustand', 'Anschaffungsjahr', 'Nächste Prüfung', 'Seriennummer',
        ],
    ];

    /** "Kategorie|Subkategorie" => zusätzliche Eigenschaften nur dieser Subkategorie. */
    public const SUB_CATEGORY_PROPERTIES = [
        'Bühne & Rigging|Hebezeuge' => ['Seriennummer'],
        'Verbrauchsmaterial|Kabel & Strom' => ['Stromanschluss'],
    ];

    /** Kategorie => bevorzugte Lager-Räume (erster existierender gewinnt). */
    public const CATEGORY_ROOMS = [
        'Licht' => ['Lager Technik'],
        'Ton' => ['Lager Technik'],
        'Video' => ['Lager Technik'],
        'Bühne & Rigging' => ['Lager Technik'],
        'Kostüm & Requisite' => ['Kostümfundus', 'Fundus', 'Lager Technik'],
        'Verbrauchsmaterial' => ['Lager Technik'],
        'Transport & Cases' => ['Lager Technik'],
        'Werkstatt & Werkzeug' => ['Werkstatt', 'Schreinerei', 'Lager Technik'],
    ];

    /**
     * Kategorie => Unterkategorie => Artikel.
     * [name, quantity, manufacturer, detailed?, image? (Demo/assets/fundus),
     *  props? (Eigenschaftsname => fester Wert)]
     */
    public const INVENTORY = [
        'Licht' => [
            'Moving Lights' => [
                ['name' => 'Robe Pointe', 'quantity' => 12, 'manufacturer' => 'Robe', 'detailed' => true,
                    'props' => ['Leistung (W)' => '470', 'Stromanschluss' => 'powerCON', 'Gewicht (kg)' => '15']],
                ['name' => 'Robe Esprite', 'quantity' => 6, 'manufacturer' => 'Robe', 'detailed' => true,
                    'props' => ['Leistung (W)' => '650', 'Stromanschluss' => 'powerCON TRUE1', 'Gewicht (kg)' => '28.4']],
                ['name' => 'Martin MAC Aura XB', 'quantity' => 8, 'manufacturer' => 'Martin',
                    'props' => ['Leistung (W)' => '260', 'Stromanschluss' => 'powerCON', 'Gewicht (kg)' => '5.8']],
                ['name' => 'GLP impression X4', 'quantity' => 10, 'manufacturer' => 'GLP',
                    'props' => ['Leistung (W)' => '450', 'Stromanschluss' => 'powerCON', 'Gewicht (kg)' => '8.9']],
                ['name' => 'Ayrton Diablo S', 'quantity' => 4, 'manufacturer' => 'Ayrton',
                    'props' => ['Leistung (W)' => '300', 'Stromanschluss' => 'powerCON TRUE1', 'Gewicht (kg)' => '21.8']],
            ],
            'Konventionell' => [
                ['name' => 'Stufenlinse 2kW', 'quantity' => 24, 'manufacturer' => 'ARRI',
                    'props' => ['Leistung (W)' => '2000', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '7.5']],
                ['name' => 'Stufenlinse 1kW', 'quantity' => 18, 'manufacturer' => 'ARRI',
                    'props' => ['Leistung (W)' => '1000', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '5.2']],
                ['name' => 'Profiler ETC Source Four 750W', 'quantity' => 18, 'manufacturer' => 'ETC',
                    'props' => ['Leistung (W)' => '750', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '6.9']],
                ['name' => 'PAR 64 CP62', 'quantity' => 30, 'manufacturer' => 'Thomann', 'image' => 'scheinwerfer-par64.jpg',
                    'props' => ['Leistung (W)' => '1000', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '3.4']],
                ['name' => 'Blinder 2-lite', 'quantity' => 8, 'manufacturer' => 'Showtec',
                    'props' => ['Leistung (W)' => '1300', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '4.1']],
            ],
            'Effekte' => [
                ['name' => 'Nebelmaschine Look Unique 2.1', 'quantity' => 3, 'manufacturer' => 'Look Solutions',
                    'props' => ['Leistung (W)' => '2100', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '7.5']],
                ['name' => 'Hazer MDG ATMe', 'quantity' => 2, 'manufacturer' => 'MDG', 'detailed' => true,
                    'props' => ['Leistung (W)' => '84', 'Stromanschluss' => 'Schuko', 'Gewicht (kg)' => '10.9']],
                ['name' => 'Stroboskop Martin Atomic 3000 LED', 'quantity' => 6, 'manufacturer' => 'Martin',
                    'props' => ['Leistung (W)' => '900', 'Stromanschluss' => 'powerCON', 'Gewicht (kg)' => '6.5']],
                ['name' => 'LED-Fluter RGBW 200W', 'quantity' => 12, 'manufacturer' => 'Cameo',
                    'props' => ['Leistung (W)' => '200', 'Stromanschluss' => 'powerCON', 'Gewicht (kg)' => '3.9']],
            ],
            'Steuerung & Dimmer' => [
                ['name' => 'grandMA3 light', 'quantity' => 2, 'manufacturer' => 'MA Lighting', 'detailed' => true,
                    'props' => ['Leistung (W)' => '450', 'Stromanschluss' => 'powerCON TRUE1', 'Gewicht (kg)' => '36']],
                ['name' => 'grandMA3 command wing', 'quantity' => 1, 'manufacturer' => 'MA Lighting',
                    'props' => ['Leistung (W)' => '150', 'Stromanschluss' => 'powerCON TRUE1', 'Gewicht (kg)' => '8.5']],
                ['name' => 'Dimmer 12x 2,3kW', 'quantity' => 4, 'manufacturer' => 'ETC',
                    'props' => ['Stromanschluss' => 'CEE32', 'Gewicht (kg)' => '18']],
                ['name' => 'Art-Net-Node 8-Port', 'quantity' => 6, 'manufacturer' => 'ELC',
                    'props' => ['Leistung (W)' => '15', 'Stromanschluss' => 'powerCON', 'Gewicht (kg)' => '2.3']],
            ],
        ],
        'Ton' => [
            'Mikrofonie' => [
                ['name' => 'Shure SM58', 'quantity' => 16, 'manufacturer' => 'Shure',
                    'props' => ['Gewicht (kg)' => '0.3']],
                ['name' => 'Sennheiser EW 500 G4 Funkstrecke', 'quantity' => 12, 'manufacturer' => 'Sennheiser', 'detailed' => true,
                    'props' => ['Gewicht (kg)' => '0.9']],
                ['name' => 'DPA 4066 Headset', 'quantity' => 10, 'manufacturer' => 'DPA',
                    'props' => ['Gewicht (kg)' => '0.1']],
                ['name' => 'Neumann KM184 (Paar)', 'quantity' => 4, 'manufacturer' => 'Neumann',
                    'props' => ['Gewicht (kg)' => '0.2']],
                ['name' => 'DI-Box BSS AR-133', 'quantity' => 10, 'manufacturer' => 'BSS',
                    'props' => ['Gewicht (kg)' => '1.1']],
            ],
            'Lautsprecher' => [
                ['name' => 'd&b T10 Topteil', 'quantity' => 8, 'manufacturer' => 'd&b audiotechnik',
                    'props' => ['Gewicht (kg)' => '15']],
                ['name' => 'd&b B6 Subwoofer', 'quantity' => 4, 'manufacturer' => 'd&b audiotechnik',
                    'props' => ['Gewicht (kg)' => '42']],
                ['name' => 'Monitor d&b M4', 'quantity' => 6, 'manufacturer' => 'd&b audiotechnik',
                    'props' => ['Gewicht (kg)' => '19']],
                ['name' => 'd&b V8 Line-Array-Modul', 'quantity' => 12, 'manufacturer' => 'd&b audiotechnik', 'detailed' => true,
                    'props' => ['Gewicht (kg)' => '34']],
                ['name' => 'Endstufe d&b D80', 'quantity' => 4, 'manufacturer' => 'd&b audiotechnik', 'detailed' => true,
                    'props' => ['Gewicht (kg)' => '13']],
            ],
            'Pulte & Zuspiel' => [
                ['name' => 'DiGiCo SD9', 'quantity' => 1, 'manufacturer' => 'DiGiCo', 'detailed' => true,
                    'props' => ['Gewicht (kg)' => '38']],
                ['name' => 'Yamaha QL5', 'quantity' => 1, 'manufacturer' => 'Yamaha',
                    'props' => ['Gewicht (kg)' => '22']],
                ['name' => 'Behringer X32 Rack', 'quantity' => 2, 'manufacturer' => 'Behringer',
                    'props' => ['Gewicht (kg)' => '5.5']],
                ['name' => 'Zuspiel-Laptop (QLab)', 'quantity' => 3, 'manufacturer' => 'Apple', 'detailed' => true,
                    'props' => ['Gewicht (kg)' => '1.6']],
            ],
        ],
        'Video' => [
            'Projektion' => [
                ['name' => 'Beamer Panasonic PT-RZ12K', 'quantity' => 2, 'manufacturer' => 'Panasonic', 'detailed' => true,
                    'props' => ['Leistung (W)' => '1200', 'Gewicht (kg)' => '43']],
                ['name' => 'Beamer Epson EB-L1075U', 'quantity' => 3, 'manufacturer' => 'Epson',
                    'props' => ['Leistung (W)' => '536', 'Gewicht (kg)' => '13']],
                ['name' => 'Kurzdistanz-Beamer Optoma ZH420UST', 'quantity' => 2, 'manufacturer' => 'Optoma',
                    'props' => ['Leistung (W)' => '350', 'Gewicht (kg)' => '5.5']],
            ],
            'Zuspiel & Signal' => [
                ['name' => 'Blackmagic ATEM Mini Extreme', 'quantity' => 2, 'manufacturer' => 'Blackmagic',
                    'props' => ['Leistung (W)' => '30', 'Gewicht (kg)' => '1.1']],
                ['name' => 'SDI-Kabeltrommel 50m', 'quantity' => 8, 'manufacturer' => 'Sommer Cable',
                    'props' => ['Gewicht (kg)' => '6.8']],
                ['name' => 'Vorschau-Monitor 24"', 'quantity' => 4, 'manufacturer' => 'LG',
                    'props' => ['Leistung (W)' => '40', 'Gewicht (kg)' => '3.8']],
            ],
            'Kamera & LED' => [
                ['name' => 'Kamera Sony PXW-Z190', 'quantity' => 3, 'manufacturer' => 'Sony', 'detailed' => true,
                    'props' => ['Gewicht (kg)' => '2.8']],
                ['name' => 'LED-Wall-Panel P3.9 (50x50cm)', 'quantity' => 48, 'manufacturer' => 'ROE Visual',
                    'props' => ['Leistung (W)' => '150', 'Gewicht (kg)' => '7.5']],
            ],
        ],
        'Bühne & Rigging' => [
            'Traversen' => [
                ['name' => 'Truss 3m 4-Punkt', 'quantity' => 20, 'manufacturer' => 'Global Truss',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '13.5', 'Traglast (kg)' => '500']],
                ['name' => 'Truss 2m 4-Punkt', 'quantity' => 14, 'manufacturer' => 'Global Truss',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '9.4', 'Traglast (kg)' => '500']],
                ['name' => 'Truss-Ecke 4-Punkt 90°', 'quantity' => 8, 'manufacturer' => 'Global Truss',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '6.2']],
            ],
            'Hebezeuge' => [
                ['name' => 'Kettenzug 500kg D8+', 'quantity' => 8, 'manufacturer' => 'ChainMaster', 'detailed' => true,
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '28', 'Traglast (kg)' => '500']],
                ['name' => 'Handkettenzug 250kg', 'quantity' => 6, 'manufacturer' => 'Yale',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '9', 'Traglast (kg)' => '250']],
                ['name' => 'Windenstativ VMB TE-064', 'quantity' => 4, 'manufacturer' => 'VMB',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '93', 'Traglast (kg)' => '125']],
            ],
            'Böden & Podeste' => [
                ['name' => 'Podest 2x1m höhenverstellbar', 'quantity' => 16, 'manufacturer' => 'Bütec', 'image' => 'buehnenpodest-schwarz.jpg',
                    'props' => ['Material' => 'Holz', 'Gewicht (kg)' => '42', 'Traglast (kg)' => '750']],
                ['name' => 'Tanzboden schwarz (Rolle 20m)', 'quantity' => 10, 'manufacturer' => 'Harlequin',
                    'props' => ['Material' => 'Kunststoff', 'Gewicht (kg)' => '25']],
                ['name' => 'Podest-Treppe 3-stufig', 'quantity' => 4, 'manufacturer' => 'Bütec',
                    'props' => ['Material' => 'Holz', 'Gewicht (kg)' => '18']],
            ],
            'Vorhänge & Textil' => [
                ['name' => 'Molton-Vorhang schwarz 6x4m', 'quantity' => 8, 'manufacturer' => 'Gerriets',
                    'props' => ['Material' => 'Stoff', 'Gewicht (kg)' => '12']],
                ['name' => 'Horizont weiß 8x5m', 'quantity' => 2, 'manufacturer' => 'Gerriets',
                    'props' => ['Material' => 'Stoff', 'Gewicht (kg)' => '9']],
                ['name' => 'Gaze schwarz 8x5m', 'quantity' => 2, 'manufacturer' => 'Gerriets',
                    'props' => ['Material' => 'Stoff', 'Gewicht (kg)' => '4']],
            ],
        ],
        'Kostüm & Requisite' => [
            'Requisiten' => [
                ['name' => 'Stuhl Thonet (Bühnenfundus)', 'quantity' => 24, 'manufacturer' => null,
                    'props' => ['Material' => 'Holz', 'Farbe' => 'Nussbraun']],
                ['name' => 'Lederkoffer historisch', 'quantity' => 8, 'manufacturer' => null, 'image' => 'lederkoffer-historisch.jpg',
                    'props' => ['Material' => 'Leder', 'Farbe' => 'Cognac']],
                ['name' => 'Samtsessel rot', 'quantity' => 4, 'manufacturer' => null, 'image' => 'samtsessel-rot.jpg',
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Weinrot']],
                ['name' => 'Stehlampe Messing', 'quantity' => 6, 'manufacturer' => null, 'image' => 'stehlampe-messing.jpg',
                    'props' => ['Material' => 'Metall', 'Farbe' => 'Messing']],
                ['name' => 'Wandspiegel Barock', 'quantity' => 3, 'manufacturer' => null, 'image' => 'wandspiegel-barock.jpg',
                    'props' => ['Material' => 'Glas', 'Farbe' => 'Gold']],
                ['name' => 'Schreibtisch Gründerzeit', 'quantity' => 2, 'manufacturer' => null,
                    'props' => ['Material' => 'Holz', 'Farbe' => 'Eiche dunkel']],
                ['name' => 'Kerzenleuchter 5-armig', 'quantity' => 6, 'manufacturer' => null,
                    'props' => ['Material' => 'Metall', 'Farbe' => 'Silber']],
                ['name' => 'Telefon Bakelit', 'quantity' => 3, 'manufacturer' => null,
                    'props' => ['Material' => 'Kunststoff', 'Farbe' => 'Schwarz']],
                ['name' => 'Schaukelstuhl', 'quantity' => 2, 'manufacturer' => null,
                    'props' => ['Material' => 'Holz', 'Farbe' => 'Kirschbaum']],
            ],
            'Kostümfundus' => [
                ['name' => 'Herrenfrack grün', 'quantity' => 5, 'manufacturer' => null, 'image' => 'herrenfrack-gruen.jpg',
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Flaschengrün', 'Größe' => 'L']],
                ['name' => 'Abendkleid (div. Größen)', 'quantity' => 15, 'manufacturer' => null,
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Diverse', 'Größe' => 'Universal']],
                ['name' => 'Uniformjacke historisch', 'quantity' => 6, 'manufacturer' => null,
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Preußischblau', 'Größe' => 'M']],
                ['name' => 'Ballkleid Tüll', 'quantity' => 4, 'manufacturer' => null,
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Champagner', 'Größe' => 'S']],
                ['name' => 'Arbeiterkostüm 1920er', 'quantity' => 8, 'manufacturer' => null,
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Grau/Braun', 'Größe' => 'Universal']],
                ['name' => 'Sommerhut Damen', 'quantity' => 10, 'manufacturer' => null,
                    'props' => ['Material' => 'Stoff', 'Farbe' => 'Naturweiß', 'Größe' => 'Universal']],
            ],
        ],
        'Verbrauchsmaterial' => [
            'Gaffa & Tape' => [
                ['name' => 'Gaffa schwarz matt 50mm', 'quantity' => 60, 'manufacturer' => 'Advance',
                    'props' => ['Länge (m)' => '50', 'Farbe' => 'Schwarz']],
                ['name' => 'Tanzbodentape schwarz', 'quantity' => 24, 'manufacturer' => 'Harlequin',
                    'props' => ['Länge (m)' => '33', 'Farbe' => 'Schwarz']],
                ['name' => 'Gewebeband weiß 19mm', 'quantity' => 36, 'manufacturer' => 'Advance',
                    'props' => ['Länge (m)' => '25', 'Farbe' => 'Weiß']],
            ],
            'Kabel & Strom' => [
                ['name' => 'Schuko-Kabeltrommel 25m', 'quantity' => 18, 'manufacturer' => 'Brennenstuhl', 'image' => 'schuko-kabel-25m.jpg',
                    'props' => ['Länge (m)' => '25', 'Stromanschluss' => 'Schuko']],
                ['name' => 'Schuko-Verlängerung 10m', 'quantity' => 30, 'manufacturer' => 'Brennenstuhl',
                    'props' => ['Länge (m)' => '10', 'Stromanschluss' => 'Schuko']],
                ['name' => 'Adapter CEE16 auf Schuko', 'quantity' => 12, 'manufacturer' => 'PCE',
                    'props' => ['Stromanschluss' => 'CEE16']],
            ],
            'Leuchtmittel' => [
                ['name' => 'Leuchtmittel CP62 1000W', 'quantity' => 40, 'manufacturer' => 'Osram'],
                ['name' => 'Halogenlampe HPL 575W', 'quantity' => 24, 'manufacturer' => 'Osram'],
            ],
            'Kleinteile' => [
                ['name' => 'Akku AA (eneloop, 4er)', 'quantity' => 20, 'manufacturer' => 'Panasonic'],
                ['name' => 'Kabelbinder 200mm (100er)', 'quantity' => 15, 'manufacturer' => 'Hellermann'],
            ],
        ],
        'Transport & Cases' => [
            'Cases' => [
                ['name' => 'Flightcase 120x80 (Rollen)', 'quantity' => 10, 'manufacturer' => 'Amptown',
                    'props' => ['Material' => 'Holz', 'Gewicht (kg)' => '35', 'Traglast (kg)' => '120']],
                ['name' => 'Kabelcase mit Schüben', 'quantity' => 6, 'manufacturer' => 'Amptown',
                    'props' => ['Material' => 'Holz', 'Gewicht (kg)' => '48']],
                ['name' => 'Truhencase XL', 'quantity' => 8, 'manufacturer' => 'Thon',
                    'props' => ['Material' => 'Holz', 'Gewicht (kg)' => '30', 'Traglast (kg)' => '100']],
            ],
            'Transporthilfen' => [
                ['name' => 'Rollwagen 600kg', 'quantity' => 8, 'manufacturer' => 'Fetra',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '22', 'Traglast (kg)' => '600']],
                ['name' => 'Sackkarre klappbar', 'quantity' => 4, 'manufacturer' => 'Fetra',
                    'props' => ['Material' => 'Metall', 'Gewicht (kg)' => '11', 'Traglast (kg)' => '200']],
                ['name' => 'Bühnenwagen 2x1m', 'quantity' => 6, 'manufacturer' => 'Bütec',
                    'props' => ['Material' => 'Holz', 'Gewicht (kg)' => '55', 'Traglast (kg)' => '500']],
            ],
        ],
        'Werkstatt & Werkzeug' => [
            'Elektrowerkzeug' => [
                ['name' => 'Akkuschrauber Makita 18V', 'quantity' => 6, 'manufacturer' => 'Makita', 'detailed' => true],
                ['name' => 'Kappsäge Bosch GCM 8', 'quantity' => 1, 'manufacturer' => 'Bosch'],
                ['name' => 'Stichsäge Bosch GST 160', 'quantity' => 2, 'manufacturer' => 'Bosch'],
            ],
            'Handwerkzeug & Messtechnik' => [
                ['name' => 'Werkzeugkoffer Gedore', 'quantity' => 4, 'manufacturer' => 'Gedore'],
                ['name' => 'Multimeter Fluke 117', 'quantity' => 3, 'manufacturer' => 'Fluke', 'detailed' => true],
                ['name' => 'DMX-Tester', 'quantity' => 2, 'manufacturer' => 'Swisson'],
                ['name' => 'Stehleiter 8 Stufen', 'quantity' => 5, 'manufacturer' => 'Zarges',
                    'props' => ['Traglast (kg)' => '150']],
            ],
        ],
    ];
}
