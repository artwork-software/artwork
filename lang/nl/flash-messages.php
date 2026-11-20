<?php

//phpcs:disable
return [
    'permission-preset' => [
        'success' => [
            'create' => 'Rechtenprofiel aangemaakt.',
            'update' => 'Rechtenprofiel bijgewerkt.',
            'delete' => 'Rechtenprofiel verwijderd.',
        ],
        'error' => [
            'create' => 'Rechtenprofiel kon niet worden opgeslagen. Probeer het opnieuw.',
            'update' => 'Rechtenprofiel kon niet worden bijgewerkt. Probeer het opnieuw.',
            'delete' => 'Rechtenprofiel kon niet worden verwijderd. Probeer het opnieuw.',
        ]
    ],
    'branding' => [
        'update' => 'Huisstijl bijgewerkt.'
    ],
    'communication_and_legal' => [
        'update' => 'Communicatie en juridische gegevens bijgewerkt.'
    ],
    'interfaces' => [
        'failed_to_save' => 'Instellingen van de Sage-koppeling konden niet worden bijgewerkt. Probeer het opnieuw.',
        'connection_test_failed' => 'De instellingen van de Sage-koppeling zijn bijgewerkt, maar de verbindingstest is mislukt. Controleer de instellingen en of de koppeling bereikbaar is.',
        'saved_successfully' => 'Instellingen van de Sage-koppeling bijgewerkt.',
        'import_executed_successfully' => 'Sage-import voltooid.',
        'import_executed_unsuccessfully' => 'Sage-import kon niet worden uitgevoerd. Probeer het opnieuw.',
        'date_range_required' => 'Geef een datumbereik op (vanaf datum).',
        'date_or_ktr_required' => 'Geef minimaal een KTR of een datumbereik op.',
        'booking_days_deleted_successfully' => 'Boekingsgegevens verwijderd.',
    ],
    'shift-qualification' => [
        'success' => [
            'create' => 'Kwalificatie opgeslagen.',
            'update' => 'Kwalificatie bijgewerkt.',
            'destroy' => 'Kwalificatie verwijderd.'
        ],
        'error' => [
            'create' => 'Kwalificatie kon niet worden opgeslagen. Probeer het opnieuw.',
            'update' => 'Kwalificatie kon niet worden bijgewerkt. Probeer het opnieuw.',
            'destroy' => 'Kwalificatie kon niet worden bijgewerkt. Probeer het opnieuw.'
        ]
    ],
    'budget-general-setting' => [
        'success' => [
            'update' => 'Instelling opgeslagen.'
        ],
        'error' => [
            'update' => 'Instelling kon niet worden opgeslagen. Probeer het opnieuw.'
        ]
    ],
    'budget-account-management' => [
        'success' => [
            'account' => [
                'create' => 'Rekening opgeslagen.',
                'update' => 'Rekening bijgewerkt.',
                'delete' => 'Rekening verwijderd.'
            ],
            'cost-unit' => [
                'create' => 'Kosteneenheid opgeslagen.',
                'update' => 'Kosteneenheid bijgewerkt.',
                'delete' => 'Kosteneenheid verwijderd.'
            ]
        ],
        'error' => [
            'account' => [
                'create' => 'Rekening kon niet worden opgeslagen. Probeer het opnieuw.',
                'update' => 'Rekening kon niet worden bijgewerkt. Probeer het opnieuw.',
                'delete' => 'Rekening kon niet worden verwijderd. Probeer het opnieuw.'
            ],
            'cost-unit' => [
                'create' => 'Kosteneenheid kon niet worden opgeslagen. Probeer het opnieuw.',
                'update' => 'Kosteneenheid kon niet worden bijgewerkt. Probeer het opnieuw.',
                'delete' => 'Kosteneenheid kon niet worden verwijderd. Probeer het opnieuw.'
            ]
        ]
    ],
    'budget-drag-and-drop' => [
        'success' => [
            'drop' => 'Begroting verplaatst.',
            'restore' => 'Begroting hersteld.',
            'delete' => 'Begroting verwijderd.',
            'force-delete' => 'Begroting definitief verwijderd.'
        ],
        'error' => [
            'update' => 'Begroting kon niet worden bijgewerkt. Probeer het opnieuw.',
            'drop' => 'Begroting kon niet worden verplaatst. Probeer het opnieuw. De waarden in kolom 1 of 2 komen niet overeen.',
            'restore' => 'Begroting kon niet worden hersteld. Probeer het opnieuw.',
            'delete' => 'Begroting kon niet worden verwijderd. Probeer het opnieuw.',
        ]
    ],
    'inventory-management' => [
        'column' => [
            'errors' => [
                'create' => 'Kolom kon niet worden opgeslagen. Probeer het opnieuw.',
                'duplicate' => 'Kolom kon niet worden gedupliceerd. Probeer het opnieuw.',
                'updateName' => 'Kolomnaam kon niet worden bijgewerkt. Probeer het opnieuw.',
                'updateBackgroundColor' => 'Kolomkleur kon niet worden bijgewerkt. Probeer het opnieuw.',
                'updateTypeOptions' => 'Keuzeopties van de kolom konden niet worden bijgewerkt. Probeer het opnieuw.',
                'delete' => 'Kolom kon niet worden verwijderd. Probeer het opnieuw.'
            ]
        ],
        'category' => [
            'errors' => [
                'create' => 'Categorie kon niet worden opgeslagen. Probeer het opnieuw.',
                'updateName' => 'Categorienaam kon niet worden bijgewerkt. Probeer het opnieuw.',
                'updateOrder' => 'Positie van de categorie kon niet worden bijgewerkt. Probeer het opnieuw.',
                'delete' => 'Categorie kon niet worden verwijderd. Probeer het opnieuw.'
            ]
        ],
        'group' => [
            'errors' => [
                'create' => 'Groep kon niet worden opgeslagen. Probeer het opnieuw.',
                'updateName' => 'Groepsnaam kon niet worden bijgewerkt. Probeer het opnieuw.',
                'updateOrder' => 'Positie van de groep kon niet worden bijgewerkt. Probeer het opnieuw.',
                'delete' => 'Groep kon niet worden verwijderd. Probeer het opnieuw.'
            ]
        ],
        'item' => [
            'errors' => [
                'create' => 'Item kon niet worden opgeslagen. Probeer het opnieuw.',
                'updateOrder' => 'Positie van het item kon niet worden bijgewerkt. Probeer het opnieuw.',
                'delete' => 'Item kon niet worden verwijderd. Probeer het opnieuw.'
            ]
        ],
        'item-cell' => [
            'errors' => [
                'updateCellValue' => 'Waarde kon niet worden bijgewerkt. Probeer het opnieuw.'
            ]
        ],
        'filter' => [
            'errors' => [
                'updateOrCreate' => 'Filterinstellingen konden niet worden bijgewerkt. Probeer het opnieuw.'
            ]
        ],
        'export' => [
            'errors' => [
                'download' => 'Export kon niet worden aangemaakt. Probeer het opnieuw.'
            ]
        ]
    ],
    'module-settings' => [
        'success' => [
            'update' => 'Zichtbaarheid van modules bijgewerkt.'
        ]
    ],
    'shift-settings' => [
        'error' => [
            'update' => 'Dienstinstelling kon niet worden opgeslagen. Probeer het opnieuw.'
        ]
    ],
    'external_user_source' => [
        'success' => [
            'create' => 'Externe gebruikersbron aangemaakt.',
            'update' => 'Externe gebruikersbron bijgewerkt.',
            'delete' => 'Externe gebruikersbron verwijderd.'
        ]
    ],
    'external_user_group_mapping' => [
        'success' => [
            'create' => 'Groepskoppeling aangemaakt.',
            'update' => 'Groepskoppeling bijgewerkt.',
            'delete' => 'Groepskoppeling verwijderd.'
        ]
    ],
    'oidc' => [
        'error' => [
            'authentication_failed' => 'Aanmelden via de identiteitsprovider is mislukt. Probeer het opnieuw.',
            'missing_email' => 'De identiteitsprovider heeft geen e-mailadres teruggegeven. Aanmelden is niet mogelijk.',
            'domain_not_allowed' => 'Aanmelden met jouw e-maildomein is niet toegestaan.',
            'password_login_disabled' => 'Dit account meldt zich aan via de identiteitsprovider. Gebruik de SSO-knop.',
            'email_not_verified' => 'Je identiteitsprovider heeft je e-mailadres niet als geverifieerd gemeld. Neem contact op met je beheerder.',
            'account_deleted' => 'Je artwork-account is verwijderd. Neem contact op met je beheerder als je opnieuw toegang nodig hebt.',
            'multi_tenant_not_allowed' => 'Deze aanmelding is onjuist geconfigureerd (multi-tenant-endpoint). Neem contact op met je beheerder.'
        ]
    ],
    'user' => [
        'error' => [
            'account_deactivated' => 'Je artwork-account is gedeactiveerd. Neem contact op met je beheerder als je opnieuw toegang nodig hebt.',
            'cannot_deactivate_self' => 'Je kunt je eigen account niet deactiveren.',
            'cannot_deactivate_last_local_admin' => 'Het laatste beheerdersaccount met een lokale aanmelding kan niet worden gedeactiveerd.',
        ],
        'success' => [
            'deactivated' => 'Account gedeactiveerd',
            'reactivated' => 'Account opnieuw geactiveerd',
        ]
    ]
];
