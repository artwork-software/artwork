<?php

//phpcs:disable
return [
    'permission-preset' => [
        'success' => [
            'create' => 'Rechte-Vorlage erstellt',
            'update' => 'Rechte-Vorlage aktualisiert',
            'delete' => 'Rechte-Vorlage gelöscht',
        ],
        'error' => [
            'create' => 'Rechte-Vorlage konnte nicht gespeichert werden. Bitte versuche es erneut',
            'update' => 'Rechte-Vorlage konnte nicht aktualisiert werden. Bitte versuche es erneut',
            'delete' => 'Rechte-Vorlage konnte nicht gelöscht werden. Bitte versuche es erneut',
        ]
    ],
    'branding' => [
        'update' => 'Branding aktualisiert'
    ],
    'communication_and_legal' => [
        'update' => 'Kommunikation & Rechtliches aktualisiert'
    ],
    'interfaces' => [
        'failed_to_save' => 'Sage-Schnittstelleneinstellungen konnten nicht aktualisiert werden, bitte erneut versuchen.',
        'connection_test_failed' => 'Sage-Schnittstelleneinstellungen wurden erfolgreich aktualisiert, aber der Verbindungstest ist fehlgeschlagen. Bitte überprüfe die Schnittstelleneinstellungen und stelle sicher, dass die Schnittstelle erreichbar ist.',
        'saved_successfully' => 'Sage-Schnittstelleneinstellungen aktualisiert',
        'import_executed_successfully' => 'Sage-Import ausgeführt',
        'import_executed_unsuccessfully' => 'Sage-Import konnte nicht ausgeführt werden, bitte erneut versuchen.',
        'date_range_required' => 'Bitte gib einen Zeitraum (Von-Datum) an.',
        'date_or_ktr_required' => 'Bitte gib mindestens einen KTR oder einen Zeitraum an.',
        'booking_days_deleted_successfully' => 'Buchungsdaten gelöscht',
    ],
    'shift-qualification' => [
        'success' => [
            'create' => 'Funktion gespeichert',
            'update' => 'Funktion aktualisiert',
            'destroy' => 'Funktion gelöscht'
        ],
        'error' => [
            'create' => 'Funktion konnte nicht gespeichert werden, bitte versuche es erneut.',
            'update' => 'Funktion konnte nicht aktualisiert werden, bitte versuche es erneut.',
            'destroy' => 'Funktion konnte nicht gelöscht werden, bitte versuche es erneut.'
        ]
    ],
    'budget-general-setting' => [
        'success' => [
            'update' => 'Einstellung gespeichert'
        ],
        'error' => [
            'update' => 'Einstellung konnte nicht gespeichert werden, bitte versuche es erneut.'
        ]
    ],
    'budget-account-management' => [
        'success' => [
            'account' => [
                'create' => 'Konto gespeichert',
                'update' => 'Konto aktualisiert',
                'delete' => 'Konto gelöscht'
            ],
            'cost-unit' => [
                'create' => 'Kostenstelle gespeichert',
                'update' => 'Kostenstelle aktualisiert',
                'delete' => 'Kostenstelle gelöscht'
            ]
        ],
        'error' => [
            'account' => [
                'create' => 'Konto konnte nicht gespeichert werden, bitte erneut versuchen.',
                'update' => 'Konto konnte nicht aktualisiert werden, bitte erneut versuchen.',
                'delete' => 'Konto konnte nicht gelöscht werden, bitte erneut versuchen.'
            ],
            'cost_unit' => [
                'create' => 'Kostenstelle konnte nicht gespeichert werden, bitte erneut versuchen.',
                'update' => 'Kostenstelle konnte nicht aktualisiert werden, bitte erneut versuchen.',
                'delete' => 'Kostenstelle konnte nicht gelöscht werden, bitte erneut versuchen.'
            ]
        ]
    ],
    'budget-drag-and-drop' => [
        'success' => [
            'drop' => 'Budget verschoben',
            'restore' => 'Budget wiederhergestellt',
            'delete' => 'Budget gelöscht',
            'force-delete' => 'Budget endgültig gelöscht'
        ],
        'error' => [
            'update' => 'Budget konnte nicht aktualisiert werden, bitte versuche es erneut.',
            'drop' => 'Budget konnte nicht verschoben werden, bitte versuche es erneut. Wert aus Spalte 1 oder Spalte 2 stimmen nicht überein.',
            'restore' => 'Budget konnte nicht wiederhergestellt werden, bitte versuche es erneut.',
            'delete' => 'Budget konnte nicht gelöscht werden, bitte versuche es erneut.',
        ]
    ],
    'inventory-management' => [
        'column' => [
            'errors' => [
                'create' => 'Spalte konnte nicht gespeichert werden. Bitte versuche es erneut.',
                'duplicate' => 'Spalte konnte nicht dupliziert werden. Bitte versuche es erneut.',
                'updateName' => 'Spaltenname konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'updateBackgroundColor' => 'Spaltenfarbe konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'updateTypeOptions' => 'Spalten-Auswahloptionen konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'delete' => 'Spalte konnte nicht gelöscht werden. Bitte versuche es erneut.'
            ]
        ],
        'category' => [
            'errors' => [
                'create' => 'Kategorie konnte nicht gespeichert werden. Bitte versuche es erneut.',
                'updateName' => 'Kategoriename konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'updateOrder' => 'Kategorieposition konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'delete' => 'Kategorie konnte nicht gelöscht werden. Bitte versuche es erneut.'
            ]
        ],
        'group' => [
            'errors' => [
                'create' => 'Gruppe konnte nicht gespeichert werden. Bitte versuche es erneut.',
                'updateName' => 'Gruppenname konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'updateOrder' => 'Gruppenposition konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'delete' => 'Gruppe konnte nicht gelöscht werden. Bitte versuche es erneut.'
            ]
        ],
        'item' => [
            'errors' => [
                'create' => 'Gegenstand konnte nicht gespeichert werden. Bitte versuche es erneut.',
                'updateOrder' => 'Gegenstandsposition konnte nicht aktualisiert werden. Bitte versuche es erneut.',
                'delete' => 'Gegenstand konnte nicht gelöscht werden. Bitte versuche es erneut.'
            ]
        ],
        'item-cell' => [
            'errors' => [
                'updateCellValue' => 'Wert konnte nicht aktualisiert werden. Bitte versuche es erneut.'
            ]
        ],
        'filter' => [
            'errors' => [
                'updateOrCreate' => 'Filtereinstellungen konnten nicht aktualisiert werden. Bitte versuche es erneut.'
            ]
        ],
        'export' => [
            'errors' => [
                'download' => 'Export konnte nicht erzeugt werden. Bitte erneut versuchen.'
            ]
        ]
    ],
    'module-settings' => [
        'success' => [
            'update' => 'Modul-Sichtbarkeit aktualisiert'
        ]
    ],
    'shift-settings' => [
        'error' => [
            'update' => 'Schicht-Einstellung konnte nicht gespeichert werden. Bitte versuche es erneut.'
        ]
    ],
    'external_user_source' => [
        'success' => [
            'create' => 'Externe Nutzer*innenquelle erstellt',
            'update' => 'Externe Nutzer*innenquelle aktualisiert',
            'delete' => 'Externe Nutzer*innenquelle gelöscht'
        ]
    ],
    'external_user_group_mapping' => [
        'success' => [
            'create' => 'Gruppen-Mapping erstellt',
            'update' => 'Gruppen-Mapping aktualisiert',
            'delete' => 'Gruppen-Mapping gelöscht'
        ]
    ],
    'oidc' => [
        'error' => [
            'authentication_failed' => 'Anmeldung über den Identity Provider fehlgeschlagen. Bitte versuche es erneut.',
            'missing_email' => 'Der Identity Provider hat keine E-Mail-Adresse übermittelt. Anmeldung nicht möglich.',
            'domain_not_allowed' => 'Deine E-Mail-Domain ist für diese Anmeldung nicht zugelassen.',
            'password_login_disabled' => 'Dieser Account meldet sich über den Identity Provider an. Bitte nutze den SSO-Button.'
        ]
    ]
];
