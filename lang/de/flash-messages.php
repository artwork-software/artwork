<?php

//phpcs:disable
return [
    'permission-preset' => [
        'success' => [
            'create' => 'Rechte-Preset erfolgreich erstellt',
            'update' => 'Rechte-Preset erfolgreich aktualisiert',
            'delete' => 'Rechte-Preset erfolgreich gelöscht',
        ],
        'error' => [
            'create' => 'Rechte-Preset konnte nicht gespeichert werden. Bitte versuche es erneut',
            'update' => 'Rechte-Preset konnte nicht aktualisiert werden. Bitte versuche es erneut',
            'delete' => 'Rechte-Preset konnte nicht gelöscht werden. Bitte versuche es erneut',
        ]
    ],
    'branding' => [
        'update' => 'Branding erfolgreich aktualisiert'
    ],
    'communication_and_legal' => [
        'update' => 'Kommunikation & Rechtliches erfolgreich aktualisiert'
    ],
    'interfaces' => [
        'failed_to_save' => 'Sage-Schnittstelleneinstellungen konnten nicht aktualisiert werden, bitte erneut versuchen.',
        'connection_test_failed' => 'Sage-Schnittstelleneinstellungen wurden erfolgreich aktualisiert, aber der Verbindungstest ist fehlgeschlagen. Bitte überprüfe die Schnittstelleneinstellungen und stelle sicher, dass die Schnittstelle erreichbar ist.',
        'saved_successfully' => 'Sage-Schnittstelleneinstellungen erfolgreich aktualisiert.',
        'import_executed_successfully' => 'Sage-Import wurde erfolgreich ausgeführt.',
        'import_executed_unsuccessfully' => 'Sage-Import konnte nicht ausgeführt werden, bitte erneut versuchen.'
    ],
    'shift-qualification' => [
        'success' => [
            'create' => 'Qualifikation erfolgreich gespeichert.',
            'update' => 'Qualifikation erfolgreich aktualisiert.'
        ],
        'error' => [
            'create' => 'Qualifikation konnte nicht gespeichert werden, bitte versuche es erneut.',
            'update' => 'Qualifikation konnte nicht aktualisiert werden, bitte versuche es erneut.'
        ]
    ],
    'budget-general-setting' => [
        'success' => [
            'update' => 'Einstellung wurde erfolgreich gespeichert.'
        ],
        'error' => [
            'update' => 'Einstellung konnte nicht gespeichert werden, bitte versuche es erneut.'
        ]
    ],
    'budget-account-management' => [
        'success' => [
            'account' => [
                'create' => 'Konto erfolgreich gespeichert.',
                'update' => 'Konto erfolgreich aktualisiert.',
                'delete' => 'Konto erfolgreich gelöscht.'
            ],
            'cost-unit' => [
                'create' => 'Kostenstelle erfolgreich gespeichert.',
                'update' => 'Kostenstelle erfolgreich aktualisiert.',
                'delete' => 'Kostenstelle erfolgreich gelöscht.'
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
            'drop' => 'Budget erfolgreich verschoben.',
            'restore' => 'Budget erfolgreich wiederhergestellt.',
            'delete' => 'Budget erfolgreich gelöscht.',
            'force-delete' => 'Budget erfolgreich endgültig gelöscht.'
        ],
        'error' => [
            'update' => 'Budget konnte nicht aktualisiert werden, bitte versuche es erneut.',
            'drop' => 'Budget konnte nicht verschoben werden, bitte versuche es erneut. Wert aus Spalte 1 oder Spalte 2 stimmen nicht überein.',
            'restore' => 'Budget konnte nicht wiederhergestellt werden, bitte versuche es erneut.',
            'delete' => 'Budget konnte nicht gelöscht werden, bitte versuche es erneut.',
        ]
    ],
];
