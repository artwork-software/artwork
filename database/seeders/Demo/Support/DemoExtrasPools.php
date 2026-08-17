<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * Kuratierte Pools für Inventar, Materialausgaben und Künstler*innen.
 */
final class DemoExtrasPools
{
    /** Kategorie => Unterkategorie => Artikel [name, quantity, manufacturer, detailed?] */
    public const INVENTORY = [
        'Licht' => [
            'Moving Lights' => [
                ['name' => 'Robe Pointe', 'quantity' => 12, 'manufacturer' => 'Robe', 'detailed' => true],
                ['name' => 'Martin MAC Aura XB', 'quantity' => 8, 'manufacturer' => 'Martin'],
                ['name' => 'GLP impression X4', 'quantity' => 10, 'manufacturer' => 'GLP'],
            ],
            'Konventionell' => [
                ['name' => 'Stufenlinse 2kW', 'quantity' => 24, 'manufacturer' => 'ARRI'],
                ['name' => 'Profiler ETC Source Four 750W', 'quantity' => 18, 'manufacturer' => 'ETC'],
                ['name' => 'PAR 64 CP62', 'quantity' => 30, 'manufacturer' => 'Thomann'],
            ],
            'Effekte' => [
                ['name' => 'Nebelmaschine Look Unique 2.1', 'quantity' => 3, 'manufacturer' => 'Look Solutions'],
                ['name' => 'Hazer MDG ATMe', 'quantity' => 2, 'manufacturer' => 'MDG', 'detailed' => true],
            ],
        ],
        'Ton' => [
            'Mikrofonie' => [
                ['name' => 'Shure SM58', 'quantity' => 16, 'manufacturer' => 'Shure'],
                ['name' => 'Sennheiser EW 500 G4 Funkstrecke', 'quantity' => 12, 'manufacturer' => 'Sennheiser', 'detailed' => true],
                ['name' => 'DPA 4066 Headset', 'quantity' => 10, 'manufacturer' => 'DPA'],
            ],
            'Lautsprecher' => [
                ['name' => 'd&b T10 Topteil', 'quantity' => 8, 'manufacturer' => 'd&b audiotechnik'],
                ['name' => 'd&b B6 Subwoofer', 'quantity' => 4, 'manufacturer' => 'd&b audiotechnik'],
                ['name' => 'Monitor d&b M4', 'quantity' => 6, 'manufacturer' => 'd&b audiotechnik'],
            ],
        ],
        'Video' => [
            'Projektion' => [
                ['name' => 'Beamer Panasonic PT-RZ12K', 'quantity' => 2, 'manufacturer' => 'Panasonic', 'detailed' => true],
                ['name' => 'Beamer Epson EB-L1075U', 'quantity' => 3, 'manufacturer' => 'Epson'],
            ],
            'Zuspiel & Signal' => [
                ['name' => 'Blackmagic ATEM Mini Extreme', 'quantity' => 2, 'manufacturer' => 'Blackmagic'],
                ['name' => 'SDI-Kabeltrommel 50m', 'quantity' => 8, 'manufacturer' => 'Sommer Cable'],
            ],
        ],
        'Bühne & Rigging' => [
            'Traversen' => [
                ['name' => 'Truss 3m 4-Punkt', 'quantity' => 20, 'manufacturer' => 'Global Truss'],
                ['name' => 'Truss 2m 4-Punkt', 'quantity' => 14, 'manufacturer' => 'Global Truss'],
            ],
            'Hebezeuge' => [
                ['name' => 'Kettenzug 500kg D8+', 'quantity' => 8, 'manufacturer' => 'ChainMaster', 'detailed' => true],
                ['name' => 'Handkettenzug 250kg', 'quantity' => 6, 'manufacturer' => 'Yale'],
            ],
            'Böden & Podeste' => [
                ['name' => 'Podest 2x1m höhenverstellbar', 'quantity' => 16, 'manufacturer' => 'Bütec'],
                ['name' => 'Tanzboden schwarz (Rolle 20m)', 'quantity' => 10, 'manufacturer' => 'Harlequin'],
            ],
        ],
        'Kostüm & Requisite' => [
            'Requisiten' => [
                ['name' => 'Stuhl Thonet (Bühnenfundus)', 'quantity' => 24, 'manufacturer' => 'Fundus'],
                ['name' => 'Lederkoffer historisch', 'quantity' => 8, 'manufacturer' => 'Fundus',
                    'image' => 'lederkoffer-historisch.jpg'],
                ['name' => 'Samtsessel rot', 'quantity' => 4, 'manufacturer' => 'Fundus',
                    'image' => 'samtsessel-rot.jpg'],
                ['name' => 'Stehlampe Messing', 'quantity' => 6, 'manufacturer' => 'Fundus',
                    'image' => 'stehlampe-messing.jpg'],
                ['name' => 'Wandspiegel Barock', 'quantity' => 3, 'manufacturer' => 'Fundus',
                    'image' => 'wandspiegel-barock.jpg'],
            ],
            'Kostümfundus' => [
                ['name' => 'Herrenfrack grün', 'quantity' => 5, 'manufacturer' => 'Fundus',
                    'image' => 'herrenfrack-gruen.jpg'],
                ['name' => 'Abendkleid (div. Größen)', 'quantity' => 15, 'manufacturer' => 'Fundus'],
            ],
        ],
        'Verbrauchsmaterial' => [
            'Gaffa & Tape' => [
                ['name' => 'Gaffa schwarz matt 50mm', 'quantity' => 60, 'manufacturer' => 'Advance'],
                ['name' => 'Tanzbodentape schwarz', 'quantity' => 24, 'manufacturer' => 'Harlequin'],
            ],
            'Kabel & Strom' => [
                ['name' => 'Schuko-Kabeltrommel 25m', 'quantity' => 18, 'manufacturer' => 'Brennenstuhl',
                    'image' => 'schuko-kabel-25m.jpg'],
            ],
            'Leuchtmittel' => [
                ['name' => 'Leuchtmittel CP62 1000W', 'quantity' => 40, 'manufacturer' => 'Osram'],
            ],
        ],
    ];

    /** Bild-Zuordnung für bereits definierte Artikel (Dateien in Demo/assets/fundus). */
    public const ARTICLE_IMAGES = [
        'PAR 64 CP62' => 'scheinwerfer-par64.jpg',
        'Podest 2x1m höhenverstellbar' => 'buehnenpodest-schwarz.jpg',
    ];

    /* -----------------------------------------------------------------
     | CRM: kuratierte Kontakte ohne Entity (Veranstalter, Sponsoren)
     | ----------------------------------------------------------------- */

    public const CRM_TYPES = [
        'veranstalter' => ['name' => 'Veranstalter', 'icon' => 'IconBuilding', 'color' => '#2563eb'],
        'sponsor' => ['name' => 'Sponsor', 'icon' => 'IconHeartHandshake', 'color' => '#059669'],
    ];

    /** typeKey => Kontakte mit Basiseigenschaften (Property-Namen des Systems). */
    public const CRM_CONTACTS = [
        'veranstalter' => [
            ['display_name' => 'Theater Lüneburg', 'Email' => 'kbb@theater-lueneburg.example',
                'Telefon' => '+49 4131 555 22 11', 'Stadt' => 'Lüneburg', 'Land' => 'Deutschland'],
            ['display_name' => 'Compagnie Marelle (Management)', 'Email' => 'booking@cie-marelle.example',
                'Telefon' => '+33 4 72 55 10 20', 'Stadt' => 'Lyon', 'Land' => 'Frankreich'],
            ['display_name' => 'Teatro Luna (Tourbüro)', 'Email' => 'gira@teatroluna.example',
                'Telefon' => '+34 954 55 66 77', 'Stadt' => 'Sevilla', 'Land' => 'Spanien'],
            ['display_name' => 'Festivalbüro Nordwind', 'Email' => 'produktion@nordwind-festival.example',
                'Telefon' => '+49 30 555 91 91', 'Stadt' => 'Berlin', 'Land' => 'Deutschland'],
        ],
        'sponsor' => [
            ['display_name' => 'Kulturstiftung Nord', 'Email' => 'foerderung@kulturstiftung-nord.example',
                'Telefon' => '+49 40 555 70 00', 'Stadt' => 'Hamburg', 'Land' => 'Deutschland'],
            ['display_name' => 'Hanseatic Insurance AG', 'Email' => 'sponsoring@hanseatic-insurance.example',
                'Telefon' => '+49 40 555 80 00', 'Stadt' => 'Hamburg', 'Land' => 'Deutschland'],
            ['display_name' => 'Stadtwerke Hamburg-Mitte', 'Email' => 'kultur@swhm.example',
                'Telefon' => '+49 40 555 60 00', 'Stadt' => 'Hamburg', 'Land' => 'Deutschland'],
        ],
    ];

    public const EXTERNAL_ISSUES = [
        [
            'name' => 'Leihgabe Theater Lüneburg – Funkstrecken',
            'external_name' => 'Theater Lüneburg',
            'external_email' => 'technik@theater-lueneburg.example',
            'external_phone' => '+49 4131 555 22 11',
            'external_address' => 'An den Reeperbahnen 3, 21335 Lüneburg',
            'material_value' => 4800.00,
            'overdue' => true,
        ],
        [
            'name' => 'Leihgabe Freie Szene HH – Podeste',
            'external_name' => 'Perfomancekollektiv Speicher e.V.',
            'external_email' => 'orga@kollektiv-speicher.example',
            'external_phone' => '+49 40 555 88 77',
            'external_address' => 'Speicherstraße 44, 20457 Hamburg',
            'material_value' => 1200.00,
            'overdue' => false,
        ],
    ];

    public const ARTISTS = [
        ['first_name' => 'Mara', 'last_name' => 'Ilić', 'position' => 'Choreografin'],
        ['first_name' => 'Jule', 'last_name' => 'Brandes', 'position' => 'Regisseurin'],
        ['first_name' => 'Omar', 'last_name' => 'Khaled', 'position' => 'Regisseur'],
        ['first_name' => 'Selma', 'last_name' => 'Kron', 'position' => 'Mezzosopranistin'],
        ['first_name' => 'Pavel', 'last_name' => 'Orlov', 'position' => 'Pianist'],
        ['first_name' => 'Ada', 'last_name' => 'Lund', 'position' => 'Cellistin'],
        ['first_name' => 'Jasper', 'last_name' => 'Thiel', 'position' => 'Autor'],
        ['first_name' => 'Camille', 'last_name' => 'Roche', 'position' => 'Tänzerin (Cie. Marelle)'],
        ['first_name' => 'Élodie', 'last_name' => 'Marchand', 'position' => 'Tänzerin (Cie. Marelle)'],
        ['first_name' => 'Tomás', 'last_name' => 'Vega', 'position' => 'Performer (Teatro Luna)'],
        ['first_name' => 'Ines', 'last_name' => 'Duarte', 'position' => 'Performerin (Teatro Luna)'],
        ['first_name' => 'Niko', 'last_name' => 'Berger', 'position' => 'Musiker'],
    ];

    public const ACCOMMODATIONS = [
        [
            'name' => 'Hotel Elbblick',
            'email' => 'reservierung@hotel-elbblick.example',
            'phone_number' => '+49 40 555 30 10',
            'street' => 'Hafenstraße 8',
            'zip_code' => '20359',
            'location' => 'Hamburg',
            'note' => 'Kontingent 5 Zimmer, Stichwort "Testhaus". Frühstück inklusive.',
            'cost_per_night' => 98.0,
        ],
        [
            'name' => 'Pension Anker',
            'email' => 'info@pension-anker.example',
            'phone_number' => '+49 40 555 41 20',
            'street' => 'Deichweg 21',
            'zip_code' => '20457',
            'location' => 'Hamburg',
            'note' => 'Günstige Alternative, 10 Min. zu Fuß zum Haus.',
            'cost_per_night' => 62.0,
        ],
        [
            'name' => 'Gästewohnung Testhaus',
            'email' => 'demo@testhaus.artwork.software',
            'phone_number' => '+49 40 555 00 00',
            'street' => 'Speicherstraße 12',
            'zip_code' => '20457',
            'location' => 'Hamburg',
            'note' => 'Hauseigene Gästewohnung, 2 Schlafzimmer, Selbstversorgung.',
            'cost_per_night' => 35.0,
        ],
    ];

    public const ROOM_TYPES = ['Einzelzimmer', 'Doppelzimmer', 'Apartment'];
}
