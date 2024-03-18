<?php

namespace Artwork\Modules\Setup\DataProvider;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;

class BaseDataProvider implements RoleAndPermissionDataProvider
{
    public function getPermissions(): array
    {
        return [
            [
                'name' => PermissionNameEnum::PROJECT_VIEW->value,
                'name_de' => "Leserechte für alle Projekte",
                'group' => 'Projekte',
                'tooltipText' => 'Nutzer*in darf sämtliche Projekte einsehen – sowohl die Projektdetails als auch die ' .
                    'Belegungen im Kalender.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
                'name_de' => "Eigene Projekte anlegen & bearbeiten",
                'group' => 'Projekte',
                'tooltipText' => 'Nutzer*in darf Projekte anlegen, bearbeiten & löschen – dadurch ist er/sie automatisch ' .
                    'Projektadmin des neu angelegten Projekts.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::WRITE_PROJECTS->value,
                'name_de' => "Schreibrechte für alle Projekte",
                'group' => 'Projekte',
                'tooltipText' => 'Nutzer*in hat auf alle Projekte Projektadmin-Rechte, auch wenn er/sie nicht zum ' .
                    'Projektteam gehört.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::PROJECT_DELETE->value,
                'name_de' => "Löschrecht für alle Projekte",
                'group' => 'Projekte',
                'tooltipText' => 'Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::PROJECT_MANAGEMENT->value,
                'name_de' => "Projektleitung sein",
                'group' => 'Projekte',
                'tooltipText' => 'User darf in Projekten Projektleitung sein.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::EVENT_REQUEST->value,
                'name_de' => "Raumbelegungen anfragen",
                'group' => 'Raumbelegungen',
                'tooltipText' => 'Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die ' .
                    'eigenen Anfragen editieren & löschen.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::CONTRACT_SEE_DOWNLOAD->value,
                'name_de' => "Darf Vertragsbausteine einsehen & runterladen",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Hier fehlt der tooltip ? ',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value,
                'name_de' => "Vertragsbausteine verwalten",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Darf Vertragsbausteine hochladen und löschen.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
                'name_de' => "Finanzierungsquellen anlegen und verwalten",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'User darf eigene Finanzierungsquellen anlegen und zur Einsicht & Verwaltung ' .
                    'von Finanzierungsquellen eingeladen werden.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::MONEY_SOURCE_EDIT_DELETE->value,
                'name_de' => "Hat auf alle Finanzierungsquellen Schreib- und Löschrechte",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Tooltip fehlt',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::PROJECT_BUDGET_SEE_DOCS_CONTRACTS->value,
                'name_de' => "Darf alle Budget-Dokumente & Verträge von allen Projekten einsehen, bearbeiten und löschen",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Tooltip fehlt',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value,
                'name_de' => "Darf zusätzlich sämtliche verifizierungs-, oder festgeschriebenen Status
             aufheben oder gesperrte Spalten entsperren.",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Tooltip fehlt',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::SETTINGS_UPDATE->value,
                'name_de' => "Tooleinstellungen editieren",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf die Grundeinstellungen des Tools editieren und z.B. Logos austauschen, ' .
                    'Impressum definieren etc.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::TEAM_UPDATE->value,
                'name_de' => "Teamverwaltung",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf Teams (Abteilungen) im System anlegen, editieren & löschen. Diese Teams ' .
                    'können anschließend z.B. Projekten zugeordnet werden.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::ROOM_UPDATE->value,
                'name_de' => "Raumverwaltung",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf Räume erstellen, löschen und bearbeiten.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value,
                'name_de' => "Systemeinstellungen für Projekte definieren",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche ' .
                    'definieren, bearbeiten & löschen.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::EVENT_SETTINGS_UPDATE->value,
                'name_de' => "Systemeinstellungen für Termine definieren",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Termintypen definieren, editieren & löschen.  ',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value,
                'name_de' => "Verwaltung von Checklisten-Vorlagen",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen ' .
                    'können anschließend von allen anderen Usern verwendet werden..',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::SYSTEM_NOTIFICATION->value,
                'name_de' => "Systemnachrichten verwalten",
                'group' => 'Systemeinstellungen',
                'tooltipText' => 'Nutzer*in darf Systemnachrichten anlegen, editieren und löschen. Diese ' .
                    'Benachrichtigungen werden allen Usern angezeigt.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::MA_MANAGER->value,
                'name_de' => "MA-Verwaltung",
                'group' => 'Mitarbeiter-Einstellungen',
                'tooltipText' => 'Darf MA Seiten anlegen und bearbeiten, aber die User nicht einladen.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::VIEW_BUDGET_TEMPLATES->value,
                'name_de' => "Budgetvorlagen einsehen",
                'group' => 'Dokumente & Budget',
                "checked" => false
            ],
            [
                'name' => PermissionNameEnum::SHIFT_PLANNER->value,
                'name_de' => "Schichtplaner",
                'group' => 'Schichten',
                'tooltipText' => 'Darf MA Seiten nicht anlegen aber die User verplanen.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::UPDATE_BUDGET_TEMPLATES->value,
                'name_de' => "Budgetvorlagen bearbeiten",
                'group' => 'Dokumente & Budget',
                "checked" => false
            ],
            [
                'name' => PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value,
                'name_de' => "Globaler Budgetzugriff ohne Dokumenteneinsicht",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen ' .
                    'Projekten einsehen ohne dabei die Dokumente sehen zu können.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                'name_de' => "Globaler Budgetzugriff mit Dokumenteneinsicht",
                'group' => 'Dokumente & Budget',
                'tooltipText' => 'Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen ' .
                    'Projekten einsehen und kann auch alle Dokumente der Projekte sehen.',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::VIEW_SHIFT_PLAN->value,
                'name_de' => "Schichtplan einsehen",
                'group' => 'Schichten',
                'tooltipText' => 'Darf den globalen Schichtplan einsehen',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::CAN_COMMIT_SHIFTS->value,
                'name_de' => "Dienstpläne festschreiben",
                'group' => 'Schichten',
                'tooltipText' => 'Darf Dienstpläne festschreiben',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::EDIT_EXTERNAL_USERS_CONDITIONS->value,
                'name_de' => "Externe Mitarbeiterkonditionen verwalten",
                'group' => 'Mitarbeiter-Einstellungen',
                'tooltipText' => 'Darf die Konditionen von externen Mitarbeitern sehen und bearbeiten',
                'checked' => false
            ],
            [
                'name' => PermissionNameEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value,
                'name_de' => "Sage-Datensätze einsehen",
                'group' => 'Schnittstellen',
                'tooltipText' => 'Nutzer*innen mit diesem Recht können nicht zugewiesene Datensätze von Sage sehen',
                'checked' => false
            ],
        ];
    }

    public function getRoles(): array
    {
        return [
            [
                'name' => RoleNameEnum::ARTWORK_ADMIN->value,
                'name_de' => "artwork-Admin",
                'tooltipText' => 'Der Admin hat alle Berechtigungen im System und kann somit alles sehen und bearbeiten.',
            ],
        ];
    }
}
