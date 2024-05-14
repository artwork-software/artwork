<?php

return [
    'project' => [
        'member' => [
            'add' => 'Du wurdest zu :project hinzugefügt',
            'remove' => 'Du wurdest aus :project entfernt',
        ],
        'leader' => [
            'add' => 'Du wurdest zur Projektleitung von :project ernannt',
            'remove' => 'Du wurdest als Projektleitung von :project entfernt',
        ],
        'budget' => [
            'add' => 'Du hast Budgetzugriff in :project erhalten',
            'remove' => 'Dein Budgetzugriff in :project wurde gelöscht',
            'new_verify_request' => 'Neue Verifizierungsanfrage',
            'delete_verify_request' => 'Verifizierungsanfrage gelöscht',
            'verify_removed' => 'Verifizierung in Budget aufgehoben',
            'fixed' => 'Budget festgeschrieben',
            'unfixed' => 'Festschreibung in Budget aufgehoben',
        ],
        'key_visual' => [
            'width' => 'Die Breite des Key Visuals sollte mindestens 1080px betragen.',
        ],
        'file' => [
            'permission_add' => 'Ein Dokument wurde für dich freigegeben',
            'changed' => 'Ein Dokument wurde geändert',
            'deleted' => 'Ein Dokument wurde gelöscht'
        ],
        'delete' => ':project wurde gelöscht',
    ],
    'contract' => [
        'add' => 'Ein Vertrag wurde für dich freigegeben',
        'delete' => 'Ein Vertrag wurde gelöscht',
    ],
    'shift' => [
        'add' => 'Du wurdest zu :shift hinzugefügt',
        'remove' => 'Du wurdest aus :shift entfernt',
        'locked' => 'Dienstplan festgeschrieben',
        'short_break' => 'Schicht mit zu kurzer Pausenzeit angelegt',
        'locked_changes' => 'Schichtänderung trotz Festschreibung :projectName :craftAbbreviation',
        'new_shift_add' => 'Neue Schichtbesetzung :craftName :craftAbbreviation',
        'conflict' => 'Konflikt mit deiner Schicht',
        'conflict_text' =>
            ':username hat dich am :date :from - :to eingetragen, entgegen deines ursprünglichen Eintrags.',
        'deleted_where_locked' => 'Schicht gelöscht trotz Festschreibung :projectName :craftAbbreviation',
        'shift_staffing' => 'Neue Schichtbesetzung :projectName :craftAbbreviation',
        'conflict_shift' => 'Schichtkonflikt :date :from - :to',
        'conflict_shift_withName' => 'Schichtkonflikt :date :craftName :craftAbbreviation',
        'your_short_break' => 'Du wurdest mit zu kurzer Ruhepause geplant',
        'worker_short_break' => 'Mitarbeiter*in mit zu kurzer Ruhepause geplant',
        'more_than_ten_days' => 'Du wurdest mehr als 10 Tage am Stück eingeplant',
        'worker_more_than_ten_days' => 'Mitarbeiter*in mehr als 10 Tage am Stück eingeplant',
        'shift_staffing_deleted' => 'Schichtbesetzung gelöscht :projectName :craftAbbreviation',
        'open_demand' => 'Der Termin :event hat noch :count offene Stellen',
        // 'Der Termin :event hat noch :count offene Stellen für die Gewerk :craft :shift_start - :shift_end',
        'open_demand_description' =>
            'Der Termin :event hat noch :count offene Stellen für die Gewerk :craft :shift',
    ],
    'event' => [
        'with_adjoining_audience' => 'Termin mit Publikum im Nebenraum',
        'adjoining_is_loud' => 'Lauter Termin im Nebenraum',
        'conflict' => 'Terminkonflikt',
        'conflict_text' => 'Konflikttermin belegt: :date_time',
        'new_room_request' => 'Neue Raumanfrage',
        'admin_message' => 'Nachricht von Raumadmin',
        'room_request_with_changed_room' => 'Raumanfrage mit Raumänderung bestätigt',
        'new_message' => 'Neue Nachricht zu Raumanfrage',
        'room_request_accept' => 'Raumanfrage bestätigt',
        'room_request_declined' => 'Raumanfrage abgesagt',
        'deleted' => 'Termin abgesagt'
    ],
    'moneySource' => [
        'add_permission' => 'Du hast Zugriff auf :moneySource erhalten',
        'remove_permission' => 'Dein Zugriff auf :moneySource wurde gelöscht',
        'deleted' => 'Finanzierungsquelle/gruppe :moneySource wurde gelöscht'
    ],
    'scheduling' => [
        'deadline_tomorrow' => 'Deadline von :checklist ist morgen erreicht',
        'deadline_over' => ':checklist hat ihre Deadline überschritten',
        'new_tasks' => ':count neue Aufgaben für dich',
        'changes_project' => 'Es gab Änderungen an :project',
        'changes_task' => 'Änderungen an :task',
        'changes_room' => 'Änderungen an :room',
        'changes_event' => 'Termin geändert',
        'public_changes_project' => 'Es gab öffentlichkeitsarbeitsrelevante Änderungen an :project',
        'changes_vacation' => 'Verfügbarkeit geändert'
    ],
    'keyWords' => [
        'concerns' => 'Betrifft: ',
        'concerns_shift' => 'Betrifft Schicht: ',
        'your_shift' => 'Deine Schicht: ',
        'not_available' => ':username ist nicht verfügbar',
        'time_period' => 'Zeitraum: :from :to',
        'concerns_time_period' => 'Betrifft Zeitraum: :start - :end',
    ],
    'department' => [
        'delete' => 'Team :department wurde gelöscht',
        'add' => 'Du wurdest zu Team :department hinzugefügt',
        'remove' => 'Du wurdest aus Team :department entfernt',
    ],
    'room' => [
        'leader' => [
            'add' => 'Du wurdest zum Raumadmin von :room ernannt',
            'remove' => 'Du wurdest als Raumadmin von :room entfernt'
        ],
    ]
];
