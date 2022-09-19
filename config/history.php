<?php

return [
    'project' => [
        'duplicated' => 'Projekt wurde dupliziert',
        'created' => 'Projekt angelegt',
        'properties' => [
            'name' => [
                'updated' => 'Projektname wurde geändert',
            ],
            'description' => [
                'added' => 'Kurzbeschreibung wurde hinzugefügt',
                'updated' => 'Kurzbeschreibung wurde geändert',
                'deleted' => 'Kurzbeschreibung wurde gelöscht',
            ],
            'number_of_participants' => [
                'added' => 'Anzahl Teilnehmer:innen hinzugefügt',
                'updated' => 'Anzahl Teilnehmer:innen geändert',
                'deleted' => 'Anzahl Teilnehmer:innen gelöscht',
            ],
            'cost_center' => [
                'added' => 'Kostenträger hinzugefügt',
                'updated' => 'Kostenträger geändert',
                'deleted' => 'Kostenträger gelöscht',
            ],
            'sector_id' => [
                'added' => 'Bereich hinzugefügt',
                'updated' => 'Bereich geändert',
                'deleted' => 'Bereich gelöscht',
            ],
            'category_id' => [
                'added' => 'Kategorie hinzugefügt',
                'updated' => 'Kategorie geändert',
                'deleted' => 'Kategorie gelöscht',
            ],
            'genre_id' => [
                'added' => 'Genre hinzugefügt',
                'updated' => 'Genre geändert',
                'deleted' => 'Genre gelöscht',
            ],
        ]
    ],

    'task' => [
        'created' => 'Aufgabe {name} zur Checkliste {checklistName} hinzugefügt',
        'destroyed' => 'Aufgabe {name} aus der Checkliste {checklistName} entfernt',
        'properties' => [
            'name' => [
                'updated' => 'Die Aufgabe {old} wurde in {name} umbenannt',
            ],
            'description' => [
                'deleted' => 'Kurzbeschreibung von Aufgabe {name} wurde entfernt',
                'updated' => 'Kurzbeschreibung von Aufgabe {name} wurde geändert',
            ],
            'deadline' => [
                'updated' => 'Die Deadline der Aufgabe {name} wurde geändert',
            ],
            'done_at' => [
                'added' => 'hat die Aufgabe {name} abgehakt',
                'deleted' => 'hat die Aufgabe {name} auf noch nicht erledigt gesetzt',
            ],
            'order' => [
                'updated' => 'Aufgabenanordnung in der Checkliste {checklistName} geändert',
            ],
        ]
    ],

    'checklist' => [
        'destroyed' => 'Checkliste {name} gelöscht',
        'properties' => [
            'name' => [
                'updated' => 'Checkliste {old} in {new} umbenannt',
            ],
        ],
    ],

];

