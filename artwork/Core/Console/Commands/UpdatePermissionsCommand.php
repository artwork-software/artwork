<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Services\ShiftSettingsPermissionService;
use Illuminate\Console\Command;

class UpdatePermissionsCommand extends Command
{
    protected $signature = 'artwork:update-permissions';
    protected $description = 'Update the permissions table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $permissions = [
            [
                'name' => PermissionEnum::CHECKLIST_USE_PERMISSION->value,
                'name_de' => "To-dos nutzen",
                'translation_key' => "Use to-dos",
                'group' => 'To-dos',
                'tooltipText' => 'Erlaubt Erstellen von Listen und To-dos im allgemeinen Bereich (Übersichtsseite) ' .
                    'und auf Projektebene, sofern durch To-do-Komponente nicht weiter eingeschränkt.',
                'tooltipKey' => 'Allows the creation of lists and to-dos in the general area (overview page) and ' .
                    'at project level, unless further restricted by the to-do component.',
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CHECKLIST_EDIT_PERMISSION->value,
                'name_de' => "To-dos verwalten",
                'translation_key' => "Manage to-dos",
                'group' => 'To-dos',
                'tooltipText' => 'Erlaubt zudem das Löschen aller Listen, unabhängig davon wer sie erstellt hat',
                'tooltipKey' => "Also allows you to delete all lists, regardless of who created them",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::AVAILABILITY_MANAGEMENT->value,
                'name_de' => "Verfügbarkeiten manuell verwalten",
                'translation_key' => "Manually manage availabilities",
                'group' => 'Shifts',
                'tooltipText' => 'Stelle die Verfügbarkeiten des Nutzer*innen ein',
                'tooltipKey' => "Set the availability of the user",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CREATE_EVENTS_WHEN_CREATING_PROJECT->value,
                'name_de' => "Termine einrichten bei neuem Projekt",
                'translation_key' => "Create events when creating a new project",
                'group' => 'Projects',
                'tooltipText' => 'Erstelle Termine, wenn ein neues Projekt erstellt wird',
                'tooltipKey' => "Create events when a new project is created",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::INVENTORY_STOCK_MANAGE->value,
                'name_de' => "Inventar-Bestand verwalten",
                'translation_key' => "Manage inventory stock",
                'group' => 'Inventory',
                'tooltipText' => 'Erlaubt das Anlegen, Bearbeiten und Löschen von Inventar-Beständen',
                'tooltipKey' => "Allows the creation, editing and deletion of inventory stocks",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::INVENTORY_PLANER->value,
                'name_de' => "Inventarplaner",
                'translation_key' => "Inventory planner",
                'group' => 'Inventory',
                'tooltipText' => 'Erlaubt die Planung von Inventar',
                'tooltipKey' => "Allows the planning of inventory",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::INVENTORY_SETTINGS->value,
                'name_de' => "Inventareinstellungen verwalten",
                'translation_key' => "Manage inventory settings",
                'group' => 'Inventory',
                'tooltipText' => 'Erlaubt den Zugriff auf und die Verwaltung aller Inventareinstellungen ' .
                    '(Kategorien, Eigenschaften, Status, Tags, Allgemein) sowie die Sichtbarkeit ' .
                    'von „Inventar" im Systemmenü',
                'tooltipKey' => 'Allows access to and management of all inventory settings ' .
                    '(categories, properties, status, tags, general) as well as the visibility ' .
                    'of "Inventory" in the system menu',
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value,
                'name_de' => "Private Kontaktdaten einsehen’",
                'translation_key' => "View private contact details",
                'group' => 'Employee settings',
                'tooltipText' => 'Darf private Kontaktdaten von Nutzer*innen einsehen',
                'tooltipKey' => "Can view private contact details of users",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value,
                'name_de' => "Termine fest planen",
                'translation_key' => "Schedule events without request",
                'group' => 'Event management',
                'tooltipText' => "Ein User mit diesem Recht darf Termine ohne Anfrage direkt fest planen in allen Räumen",
                'tooltipKey' => "A user with this permission can schedule events directly without a request in all rooms",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value,
                'name_de' => "Planungskalender einsehen und Planen",
                'translation_key' => "View and plan in the planning calendar",
                'group' => 'Event management',
                'tooltipText' => 'Ein User mit diesem Recht darf den Planungskalender einsehen und darin planen',
                'tooltipKey' => 'A user with this permission can view the planning calendar and plan within it',
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value,
                'name_de' => "Geplante Termine bearbeiten, löschen und bestätigen",
                'translation_key' => "Edit, delete and confirm scheduled events",
                'group' => 'Event management',
                'tooltipText' => "Ein User mit diesem Recht kann geplante Termine bearbeiten, löschen und bestätigen",
                'tooltipKey' => "A user with this permission can edit, delete and confirm scheduled events",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR->value,
                'name_de' => "Im Planungskalender fest planen",
                'translation_key' => "Plan fixed events in the planning calendar",
                'group' => 'Event management',
                'tooltipText' => 'Ein User mit diesem Recht darf im Planungskalender Termine direkt fest planen, ' .
                    'ohne eine Belegungsanfrage stellen zu müssen. Im normalen Kalender hat dieses Recht keine Auswirkung.',
                'tooltipKey' => 'A user with this permission can directly schedule fixed events in the planning calendar ' .
                    'without having to submit a booking request. This permission has no effect in the normal calendar.',
                'checked' => false
            ],
            [
                'name' => PermissionEnum::SET_CREATE_EDIT->value,
                'name_de' => "Sets anlegen & bearbeiten",
                'translation_key' => "Create & edit sets",
                'group' => 'Inventory',
                'tooltipText' => "Erlaubt das Erstellen und Bearbeiten von Sets",
                'tooltipKey' => "Allows creating and editing sets",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::SET_DELETE->value,
                'name_de' => "Sets löschen",
                'translation_key' => "Delete sets",
                'group' => 'Inventory',
                'tooltipText' => "Erlaubt das Löschen von Sets",
                'tooltipKey' => "Allows deleting sets",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::INVENTORY_CREATE_EDIT->value,
                'name_de' => "Inventar anlegen & bearbeiten",
                'translation_key' => "Create & edit inventory",
                'group' => 'Inventory',
                'tooltipText' => "Erlaubt das Anlegen und Bearbeiten von Inventar",
                'tooltipKey' => "Allows creating and editing inventory",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::INVENTORY_DELETE->value,
                'name_de' => "Inventar löschen",
                'translation_key' => "Delete inventory",
                'group' => 'Inventory',
                'tooltipText' => "Erlaubt das Löschen von Inventar",
                'tooltipKey' => "Allows deleting inventory",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::INVENTORY_DISPOSITION->value,
                'name_de' => "Inventardisposition",
                'translation_key' => "Inventory disposition",
                'group' => 'Inventory',
                'tooltipText' => "Erlaubt die Disposition und Verwaltung des Inventars",
                'tooltipKey' => "Allows disposition and management of inventory",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::MATERIAL_ISSUE_LOG_VIEW->value,
                'name_de' => "Logbuch Materialausgaben einsehen",
                'translation_key' => "View material issue log",
                'group' => 'Inventory',
                'tooltipText' => "Erlaubt das Einsehen des Logbuchs aller Materialausgabe-Änderungen",
                'tooltipKey' => "Allows viewing the log of all material issue changes",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value,
                'name_de' => "Schichteinstellungen einsehen und bearbeiten",
                'translation_key' => "View and edit shift settings",
                'group' => 'Shifts',
                'tooltipText' => "Erlaubt das Einsehen und Bearbeiten der Schichteinstellungen",
                'tooltipKey' => "Allows viewing and editing shift settings",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::DOCUMENT_REQUEST_CREATE->value,
                'name_de' => "Dokumentenanfragen erstellen",
                'translation_key' => "Create document requests",
                'group' => 'Documents & Budget',
                'tooltipText' => "Nutzer*in darf Dokumentenanfragen erstellen und an andere Nutzer*innen zuweisen.",
                'tooltipKey' => "User is allowed to create document requests and assign them to other users.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::DOCUMENT_REQUEST_EDIT->value,
                'name_de' => "Dokumentenanfragen bearbeiten",
                'translation_key' => "Edit document requests",
                'group' => 'Documents & Budget',
                'tooltipText' => "Nutzer*in darf Dokumentenanfragen bearbeiten und den Status ändern.",
                'tooltipKey' => "User is allowed to edit document requests and change their status.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CRM_VIEW->value,
                'name_de' => "CRM einsehen",
                'translation_key' => "View CRM",
                'group' => 'CRM',
                'tooltipText' => "Erlaubt den Zugriff auf das CRM-Modul und die Kontaktübersicht.",
                'tooltipKey' => "Allows access to the CRM module and the contact overview.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CRM_MANAGER->value,
                'name_de' => "CRM verwalten",
                'translation_key' => "Manage CRM",
                'group' => 'CRM',
                'tooltipText' => "Erlaubt das Verwalten von Kontakttypen, Eigenschaftsgruppen und Eigenschaften im CRM.",
                'tooltipKey' => "Allows managing contact types, property groups, and properties in the CRM.",
                'checked' => false
            ],

            [
                'name' => PermissionEnum::BI_EXPORT->value,
                'name_de' => "BI-Daten exportieren",
                'translation_key' => "Export BI data",
                'group' => 'Business Intelligence',
                'tooltipText' => "Erlaubt den Export von Business-Intelligence-Daten aus Projekten als Excel-Datei.",
                'tooltipKey' => "Allows exporting business intelligence data from projects as an Excel file.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::BI_DASHBOARD->value,
                'name_de' => "BI-Dashboard ansehen",
                'translation_key' => "View BI dashboard",
                'group' => 'Business Intelligence',
                'tooltipText' => "Erlaubt den Zugriff auf mandantenweite Business-Intelligence-Übersichten.",
                'tooltipKey' => "Allows access to tenant-wide business intelligence overviews.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_VIEW_OWN_ROSTER->value,
                'name_de' => "Mein Einsatzplan sehen",
                'translation_key' => "View own roster",
                'group' => 'Shifts',
                'tooltipText' => 'Darf den eigenen Einsatzplan („Mein Einsatzplan") einsehen.',
                'tooltipKey' => "User can view their own roster (\"My roster\").",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_SUBSCRIBE_SHIFT_CALENDAR->value,
                'name_de' => "Dienstplan-Kalender abonnieren",
                'translation_key' => "Subscribe to shift calendar",
                'group' => 'Shifts',
                'tooltipText' => 'Darf den eigenen Einsatzplan als Kalender-Abo (ICS) abonnieren.',
                'tooltipKey' => "User can subscribe to their roster as a calendar feed (ICS).",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_VIEW_SHIFT_USER_KPIS->value,
                'name_de' => "Personal-Infodaten im Dienstplan sehen",
                'translation_key' => "View staff info data in shift plan",
                'group' => 'Shifts',
                'tooltipText' => 'Darf das Info-Fenster mit spielzeitbezogenen Kennzahlen je Person im Dienstplan öffnen.',
                'tooltipKey' => "User can open the per-person info window with season-related KPIs in the shift plan.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_PAY_OUT_OVERTIME->value,
                'name_de' => "Überstunden auszahlen",
                'translation_key' => "Pay out overtime",
                'group' => 'Shifts',
                'tooltipText' => 'Darf Überstunden manuell ausbuchen (Auszahlung) und damit das Zeitkonto reduzieren.',
                'tooltipKey' => "User can manually book out overtime (payout) and thereby reduce the time account.",
                'checked' => false
            ],
            [
                'name' => PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value,
                'name_de' => "Stundenkonten im Dienstplan sehen",
                'translation_key' => "View hour accounts in shift plan",
                'group' => 'Shifts',
                'tooltipText' => 'Darf Arbeitszeitkonto und Wochenstunden (KW) aller Personen in den ' .
                    'Dienstplan-Ansichten sehen. Ohne diese Berechtigung sind nur die eigenen Werte sichtbar.',
                'tooltipKey' => 'User can see the work time account and weekly (CW) hours of all persons in the ' .
                    'shift plan views. Without this permission only their own values are visible.',
                'checked' => false
            ],
            [
                'name' => PermissionEnum::DAY_REMARKS_VIEW->value,
                'name_de' => "Tagesbemerkungen sehen",
                'translation_key' => "View day remarks",
                'group' => 'Event management',
                'tooltipText' => 'Darf die Tagesbemerkungs-Spalte im Kalender, Planungskalender und Dienstplan ' .
                    'sehen, sofern sie in den Systemeinstellungen aktiviert ist.',
                'tooltipKey' => 'User can see the day remarks column in the calendar, planning calendar and shift ' .
                    'plan if it is enabled in the system settings.',
                'checked' => true
            ],
            [
                'name' => PermissionEnum::DAY_REMARKS_EDIT->value,
                'name_de' => "Tagesbemerkungen bearbeiten",
                'translation_key' => "Edit day remarks",
                'group' => 'Event management',
                'tooltipText' => 'Darf Tagesbemerkungen im Kalender, Planungskalender und Dienstplan anlegen, ' .
                    'bearbeiten und löschen.',
                'tooltipKey' => 'User can create, edit and delete day remarks in the calendar, planning calendar ' .
                    'and shift plan.',
                'checked' => false
            ],
        ];

        $permissions = array_merge($permissions, ShiftSettingsPermissionService::definitions());

        foreach ($permissions as $permission) {
            $checkPermission = Permission::where('name', $permission['name'])->first();
            if (!$checkPermission) {
                Permission::create($permission);
                $this->info('Permission "' . $permission['name'] . '" created.');
            } else {
                // Update existing permission with new tooltip texts
                $checkPermission->update([
                    'name_de' => $permission['name_de'],
                    'translation_key' => $permission['translation_key'],
                    'group' => $permission['group'],
                    'tooltipText' => $permission['tooltipText'],
                    'tooltipKey' => $permission['tooltipKey'],
                    'checked' => $permission['checked'],
                ]);
                $this->info('Permission "' . $permission['name'] . '" updated.');
            }
        }
    }
}
