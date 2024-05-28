<?php

namespace Artwork\Modules\Setup\DataProvider;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;

class BaseDataProvider implements RoleAndPermissionDataProvider
{
    /**
     * @return string []
     */
    public function getPermissions(): array
    {
        return [
            [
                'name' => PermissionEnum::PROJECT_VIEW->value,
                'name_de' => "Leserechte für alle Projekte",
                'translation_key' => "Read permissions for all projects",
                'group' => 'Projects',
                'tooltipText' => 'Nutzer*in darf sämtliche Projekte einsehen –
                sowohl die Projektdetails als auch die ' .
                    'Belegungen im Kalender.',
                'tooltipKey' => "User can view all projects including project details and calendar bookings.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
                'name_de' => "Eigene Projekte anlegen & bearbeiten",
                'translation_key' => "Create and edit own projects",
                'group' => 'Projects',
                'tooltipText' => 'Nutzer*in darf Projekte anlegen, bearbeiten & löschen –
                dadurch ist er/sie automatisch ' .
                    'Projektadmin des neu angelegten Projekts.',
                'tooltipKey' => "User can create, edit, and delete projects, automatically
                becoming project admin of the created project.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::WRITE_PROJECTS->value,
                'name_de' => "Schreibrechte für alle Projekte",
                'translation_key' => "Write permissions for all projects",
                'group' => 'Projects',
                'tooltipText' => 'Nutzer*in hat auf alle Projekte Projektadmin-Rechte, auch wenn er/sie nicht zum ' .
                    'Projektteam gehört.',
                'tooltipKey' => "User has project admin rights on all projects, even if not part of the project team.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::PROJECT_DELETE->value,
                'name_de' => "Löschrecht für alle Projekte",
                'translation_key' => "Delete permissions for all projects",
                'group' => 'Projects',
                'tooltipText' => 'Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört.',
                'tooltipKey' => "User can delete all projects, even if not part of the project team.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::PROJECT_MANAGEMENT->value,
                'name_de' => "Projektleitung sein",
                'translation_key' => "Project management",
                'group' => 'Projects',
                'tooltipText' => 'User darf in Projekten Projektleitung sein.',
                'tooltipKey' => "User can act as project manager within projects.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::EVENT_REQUEST->value,
                'name_de' => "Raumbelegungen anfragen",
                'translation_key' => "Request room bookings",
                'group' => 'Room bookings',
                'tooltipText' => 'Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die ' .
                    'eigenen Anfragen editieren & löschen.',
                'tooltipKey' => "User can request room bookings for their own
                projects and edit or delete their own requests.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CONTRACT_SEE_DOWNLOAD->value,
                'name_de' => "Darf Vertragsbausteine einsehen & runterladen",
                'translation_key' => "Allowed to view & download contract components",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Nutzer*in darf Vertragsbausteine einsehen und runterladen.',
                'tooltipKey' => "User is allowed to view and download contract components.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CONTRACT_EDIT_UPLOAD->value,
                'name_de' => "Vertragsbausteine verwalten",
                'translation_key' => "Manage contract components",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Darf Vertragsbausteine hochladen und löschen.',
                'tooltipKey' => "User is allowed to upload and delete contract components.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
                'name_de' => "Finanzierungsquellen anlegen und verwalten",
                'translation_key' => "Create and manage funding sources",
                'group' => 'Documents & Budget',
                'tooltipText' => 'User darf eigene Finanzierungsquellen anlegen und zur Einsicht & Verwaltung ' .
                    'von Finanzierungsquellen eingeladen werden.',
                'tooltipKey' => "User is allowed to create their own funding sources
                and be invited for viewing & managing funding sources.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::MONEY_SOURCE_EDIT_DELETE->value,
                'name_de' => "Hat auf alle Finanzierungsquellen Schreib- und Löschrechte",
                'translation_key' => "Has write and delete rights on all funding sources",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Darf auf alle Finanzierungsquellen Schreib- und Löschrechte ausüben.',
                'tooltipKey' => "User has write and delete rights on all funding sources.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::PROJECT_BUDGET_SEE_DOCS_CONTRACTS->value,
                'name_de' => "Darf alle Budget-Dokumente & Verträge von allen
                Projekten einsehen, bearbeiten und löschen",
                'translation_key' => "Allowed to view, edit, and delete all
                budget documents & contracts from all projects",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Nutzer*in darf alle Budget-Dokumente & Verträge von allen Projekten einsehen, ' .
                    'bearbeiten und löschen.',
                'tooltipKey' => "User is allowed to view, edit, and delete all
                budget documents & contracts from all projects.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value,
                'name_de' => "Darf zusätzlich sämtliche verifizierungs-,
                oder festgeschriebenen Status aufheben oder gesperrte Spalten entsperren.",
                'translation_key' => "Allowed to remove any verification or fixed statuses and unlock locked columns",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Nutzer*in darf zusätzlich sämtliche verifizierungs-, oder festgeschriebenen Status ' .
                    'aufheben oder gesperrte Spalten entsperren.',
                'tooltipKey' => "User is allowed to remove any verification
                or fixed statuses and unlock locked columns.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::SETTINGS_UPDATE->value,
                'name_de' => "Tooleinstellungen editieren",
                'translation_key' => "Edit tool settings",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf die Grundeinstellungen des
                Tools editieren und z.B. Logos austauschen, ' .
                    'Impressum definieren etc.',
                'tooltipKey' => "User is allowed to edit the basic settings
                of the tool, such as replacing logos, defining legal notice, etc.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::TEAM_UPDATE->value,
                'name_de' => "Teamverwaltung",
                'translation_key' => "Team management",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf Teams (Abteilungen) im
                System anlegen, editieren & löschen. Diese Teams ' .
                    'können anschließend z.B. Projekten zugeordnet werden.',
                'tooltipKey' => "User can create, edit, and delete teams
                (departments) in the system. These teams can then be assigned to projects, for example.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::ROOM_UPDATE->value,
                'name_de' => "Raumverwaltung",
                'translation_key' => "Room management",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf Räume erstellen, löschen und bearbeiten.',
                'tooltipKey' => "User can create, delete, and edit rooms.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::PROJECT_SETTINGS_UPDATE->value,
                'name_de' => "Systemeinstellungen für Projekte definieren",
                'translation_key' => "Define system settings for projects",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche ' .
                    'definieren, bearbeiten & löschen.',
                'tooltipKey' => "User can define, edit, and delete project
                categories, genres, and areas in the system settings.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::EVENT_SETTINGS_UPDATE->value,
                'name_de' => "Systemeinstellungen für Termine definieren",
                'translation_key' => "Define system settings for appointments",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen
                Termintypen definieren, editieren & löschen.',
                'tooltipKey' => "User can define, edit, and delete types of appointments in the system settings.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value,
                'name_de' => "Verwaltung von Checklisten-Vorlagen",
                'translation_key' => "Checklist template administration",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen ' .
                    'können anschließend von allen anderen Usern verwendet werden.',
                'tooltipKey' => "User can create, edit, and delete checklist
                templates. All templates can then be used by all other users.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::SYSTEM_NOTIFICATION->value,
                'name_de' => "Systemnachrichten verwalten",
                'translation_key' => "Manage system notifications",
                'group' => 'System settings',
                'tooltipText' => 'Nutzer*in darf Systemnachrichten anlegen, editieren und löschen. Diese ' .
                    'Benachrichtigungen werden allen Usern angezeigt.',
                'tooltipKey' => "User can create, edit, and delete system
                notifications. These notifications are displayed to all users.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::MA_MANAGER->value,
                'name_de' => "MA-Verwaltung",
                'translation_key' => "Employee management",
                'group' => 'Employee settings',
                'tooltipText' => 'Darf MA Seiten anlegen und bearbeiten, aber die User nicht einladen.',
                'tooltipKey' => "User can create and edit employee pages but cannot invite users.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::VIEW_BUDGET_TEMPLATES->value,
                'name_de' => "Budgetvorlagen einsehen",
                'translation_key' => "View budget templates",
                'group' => 'Documents & Budget',
                'tooltipText' => 'User can view budget templates.',
                'tooltipKey' => "User can view budget templates.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::SHIFT_PLANNER->value,
                'name_de' => "Schichtplaner",
                'translation_key' => "Shift planner",
                'group' => 'Shifts',
                'tooltipText' => 'Darf MA Seiten nicht anlegen aber die User verplanen.',
                'tooltipKey' => "User cannot create employee pages but can schedule users.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::UPDATE_BUDGET_TEMPLATES->value,
                'name_de' => "Budgetvorlagen bearbeiten",
                'translation_key' => "Edit budget templates",
                'group' => 'Documents & Budget',
                'tooltipText' => 'User can edit budget templates.',
                'tooltipKey' => "User can edit budget templates.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value,
                'name_de' => "Globaler Budgetzugriff ohne Dokumenteneinsicht",
                'translation_key' => "Global budget access without document viewing",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen ' .
                    'Projekten einsehen ohne dabei die Dokumente sehen zu können.',
                'tooltipKey' => "User has budget access to all projects,
                meaning they can view budget planning of all projects without accessing the documents.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                'name_de' => "Globaler Budgetzugriff mit Dokumenteneinsicht",
                'translation_key' => "Global budget access with document viewing",
                'group' => 'Documents & Budget',
                'tooltipText' => 'Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen ' .
                    'Projekten einsehen und kann auch alle Dokumente der Projekte sehen.',
                'tooltipKey' => "User has budget access to all projects,
                meaning they can view both budget planning and documents of all projects.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::VIEW_SHIFT_PLAN->value,
                'name_de' => "Schichtplan einsehen",
                'translation_key' => "View shift plan",
                'group' => 'Shifts',
                'tooltipText' => 'Darf den globalen Schichtplan einsehen',
                'tooltipKey' => "User can view the global shift plan.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_COMMIT_SHIFTS->value,
                'name_de' => "Dienstpläne festschreiben",
                'translation_key' => "Lock shift plans",
                'group' => 'Shifts',
                'tooltipText' => 'Darf Dienstpläne festschreiben',
                'tooltipKey' => "User can lock shift plans.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::EDIT_EXTERNAL_USERS_CONDITIONS->value,
                'name_de' => "Externe Mitarbeiterkonditionen verwalten",
                'translation_key' => "Manage external employee conditions",
                'group' => 'Employee settings',
                'tooltipText' => 'Darf die Konditionen von externen Mitarbeitern sehen und bearbeiten',
                'tooltipKey' => "User can view and edit the conditions of external employees.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::VIEW_AND_DELETE_SAGE100_API_DATA->value,
                'name_de' => "Sage-Datensätze einsehen",
                'translation_key' => "View and delete Sage data records",
                'group' => 'Interfaces',
                'tooltipText' => 'Nutzer*innen mit diesem Recht können nicht zugewiesene Datensätze von Sage sehen.',
                'tooltipKey' => "Users with this permission can view unassigned Sage data records.",
                'checked' => false
            ],
        ];
    }

    /**
     * @return string []
     */
    public function getRoles(): array
    {
        return [
            [
                'name' => RoleEnum::ARTWORK_ADMIN->value,
                'name_de' => "artwork-Admin",
                'translation_key' => "artwork admin",
                'tooltipText' => 'Der Admin hat alle Berechtigungen im System
                und kann somit alles sehen und bearbeiten.',
                'tooltipKey' => "The admin has all permissions in the system
                 and can therefore see and edit everything.",
            ],
        ];
    }

    /**
     * @return string []
     */
    public function getExcludedPermissionColumns(): array
    {
        return ['id', 'guard_name', 'created_at', 'updated_at'];
    }

    /**
     * @return string []
     */
    public function getExcludedRoleColumns(): array
    {
        return ['id', 'guard_name', 'created_at', 'updated_at'];
    }
}
