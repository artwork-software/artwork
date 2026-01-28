<?php

//phpcs:disable
return [
    'date-heading' => 'Datum',
    'months' => [
        'january' => 'Januar',
        'february' => 'Februar',
        'march' => 'März',
        'april' => 'April',
        'may' => 'Mai',
        'june' => 'Juni',
        'july' => 'Juli',
        'august' => 'August',
        'september' => 'September',
        'october' => 'Oktober',
        'november' => 'November',
        'december' => 'Dezember',
    ],
    'shortMonths' => [
        'jan' => 'Jan',
        'feb' => 'Feb',
        'mar' => 'Mar',
        'apr' => 'Apr',
        'may' => 'Mai',
        'jun' => 'Jun',
        'jul' => 'Jul',
        'aug' => 'Aug',
        'sep' => 'Sep',
        'oct' => 'Okt',
        'nov' => 'Nov',
        'dec' => 'Dez',
    ],
    'names' => [
        'event-list-export-by-period' => 'Terminliste nach Zeitraum - :0 bis :1 - erstellt :2',
        'event-list-export-by-projects' => 'Terminliste nach Projekten - Erstellt :0 - :1',
        'event-calendar-export-by-period' => 'Kalendertermine nach Zeitraum - :0 bis :1 - erstellt :2',
        'event-calendar-export-by-projects' => 'Kalendertermine nach Projekten - Erstellt :0 - :1'
    ],
    'excel-event-list-export' => [
        'columns' => [
            'event_id' => 'Termin ID',
            'project_name' => 'Projektname',
            'artists' => 'Künstlerin',
            'start_date' => 'Datum von',
            'end_date' => 'Datum bis',
            'start_time' => 'Uhrzeit von',
            'end_time' => 'Uhrzeit bis',
            'event_type' => 'Termintyp',
            'event_name' => 'Terminname',
            'event_description' => 'Terminbeschreibung',
            'event_status' => 'Terminstatus',
            'room' => 'Raum',
            'project_team' => 'Projekttean',
            'project_properties' => 'Projekteigenschaften'
        ]
    ],
    'excel-event-calendar-export' => [
        'created-by' => 'Erstellt von :0 am :1'
    ],
    'by' => 'Export erstellt von',
    'date' => 'Exportdatum',
    'artist_name' => 'Künstlername',
    'arrival_time' => 'Ankunftszeit',
    'departure_time' => 'Abreisezeit',
    'nights_count' => 'Anzahl Übernachtungen',
    'daily_allowance' => 'Tagegeld',
    'additional_allowance' => 'Zusätzliches Tagegeld',
    'cost_per_night' => 'Kosten pro Nacht',
    'total' => 'Gesamt',
    'get_sum' => 'Betrag erhalten',
    'total_sum' => 'Gesamtbetrag',
    'total_days' => 'Gesamt Tage',
    'name_artist' => 'Name Künstler',
    'civil_name' => 'Zivilname',
    'phone_number' => 'Telefonnummer',
    'position' => 'Position',

    // Contract export translations
    'contract_name' => 'Vertragsname',
    'contract_partner' => 'Vertragspartner',
    'project' => 'Projekt',
    'contract_type' => 'Vertragstyp',
    'company_type' => 'Rechtsform',
    'amount' => 'Betrag',
    'currency' => 'Währung',
    'description' => 'Beschreibung',
    'ksk_liable' => 'KSK-pflichtig',
    'ksk_amount' => 'KSK-Betrag',
    'ksk_reason' => 'KSK-Begründung',
    'resident_abroad' => 'Wohnsitz im Ausland',
    'foreign_tax' => 'Auslandssteuer',
    'foreign_tax_amount' => 'Auslandssteuer-Betrag',
    'foreign_tax_reason' => 'Auslandssteuer-Begründung',
    'reverse_charge_amount' => 'Reverse-Charge-Betrag',
    'deadline_date' => 'Stichtag',
    'is_freed' => 'Freigegeben',
    'has_power_of_attorney' => 'Vollmacht vorhanden',
    'creator' => 'Ersteller',
    'created_at' => 'Erstellt am',
    'yes' => 'Ja',
    'no' => 'Nein',

    'shift_plan' => [
        'title'  => ':project Personalplanung',
        'period' => 'Zeitraum',

        'sections' => [
            'shifts'     => 'Schichten',
            'work_hours' => 'Arbeitsstunden',
        ],

        'subsections' => [
            'internal' => 'intern',
            'external' => 'extern',
            'total'    => 'Gesamt',
        ],

        'columns' => [
            'craft'      => 'Schicht',
            'date'       => 'Datum',
            'room'       => 'Raum',
            'start'      => 'Start',
            'end'        => 'Endzeit',
            'duration'   => 'Stunden insgesamt',
            'break_time' => 'Pausenzeit',
        ],

        'symbols' => [
            'sum' => '∑',
        ],

        'defaults' => [
            'zero_duration' => '0 Std. 00 min',
        ],
    ],
];
