<?php

namespace Database\Seeders;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_VIEW->value,
            'name_de' => "Leserechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf sämtliche Projekte einsehen – sowohl die Projektdetails als auch die Belegungen im Kalender.',
            'checked' => false
        ]);

        //Projekte
        Permission::create([
            'name' => PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
            'name_de' => "Eigene Projekte anlegen & bearbeiten",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf Projekte anlegen, bearbeiten & löschen – dadurch ist er/sie automatisch Projektadmin des neu angelegten Projekts.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::WRITE_PROJECTS->value,
            'name_de' => "Schreibrechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in hat auf alle Projekte Projektadmin-Rechte, auch wenn er/sie nicht zum Projektteam gehört.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_DELETE->value,
            'name_de' => "Löschrecht für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_MANAGEMENT->value,
            'name_de' => "Projektleitung sein",
            'group' => 'Projekte',
            'tooltipText' => 'User darf in Projekten Projektleitung sein.',
            'checked' => false
        ]);


        // Raumbelegungen
        Permission::create([
            'name' => PermissionNameEnum::EVENT_REQUEST->value,
            'name_de' => "Raumbelegungen anfragen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die eigenen Anfragen editieren & löschen.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::ROOM_ADMIN->value,
            'name_de' => "Hat Raumadminrechte bei allen Räumen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Kein Tooltip',
            'checked' => false
        ]);



        // Dokumente & Budget
        Permission::create([
            'name' => PermissionNameEnum::CONTRACT_SEE_DOWNLOAD->value,
            'name_de' => "Darf Vertragsbausteine einsehen & runterladen",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'Hier fehlt der tooltip ? ',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value,
            'name_de' => "Vertragsbausteine verwalten",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'Darf Vertragsbausteine hochladen und löschen.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
            'name_de' => "Finanzierungsquellen anlegen und verwalten",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'User darf eigene Finanzierungsquellen anlegen und zur Einsicht & Verwaltung von Finanzierungsquellen eingeladen werden.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::MONEY_SOURCE_EDIT_DELETE->value,
            'name_de' => "Hat auf alle Finanzierungsquellen Schreib- und Löschrechte",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'Tooltip fehlt',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_BUDGET_SEE_DOCS_CONTRACTS->value,
            'name_de' => "Darf alle Budget-Dokumente & Verträge von allen Projekten einsehen, bearbeiten und löschen",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'Tooltip fehlt',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_BUDGET_ADMIN->value,
            'name_de' => "Hat auf alle Projekte Budget-Zugriff",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'Tooltip fehlt',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value,
            'name_de' => "Darf zusätzlich sämtliche Verifizierungs-, oder festgeschriebene Status und gesperrte Spalten aufheben.",
            'group' => 'Dokumente & Budget',
            'tooltipText' => 'Tooltip fehlt',
            'checked' => false
        ]);


        // Systemeinstellungen
        Permission::create([
            'name' => PermissionNameEnum::SETTINGS_UPDATE->value,
            'name_de' => "Tooleinstellungen editieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf die Grundeinstellungen des Tools editieren und z.B. Logos austauschen, Impressum definieren etc.',
            'checked' => false
        ]);

        /*
         * Nur noch der Artwork Admin !!!!!
         * Permission::create([
            'name' => PermissionNameEnum::USER_UPDATE->value,
            'name_de' => "Nutzer*innenverwaltung",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf weitere Nutzer*innen einladen, bearbeiten und löschen. Zusätzlich darf er/sie Nutzerrechte für sämtliche Nutzer*innen vergeben und editieren.',
            'checked' => false
        ]);*/

        Permission::create([
            'name' => PermissionNameEnum::TEAM_UPDATE->value,
            'name_de' => "Teamverwaltung",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Teams (Abteilungen) im System anlegen, editieren & löschen. Diese Teams können anschließend z.B. Projekten zugeordnet werden.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::ROOM_UPDATE->value,
            'name_de' => "Raumverwaltung",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Räume erstellen, löschen und bearbeiten.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::PROJECT_SETTINGS_UPDATE->value,
            'name_de' => "Systemeinstellungen für Projekte definieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche definieren, bearbeiten & löschen.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::EVENT_SETTINGS_UPDATE->value,
            'name_de' => "Systemeinstellungen für Termine definieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Termintypen definieren, editieren & löschen.  ',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value,
            'name_de' => "Verwaltung von Checklisten-Vorlagen",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen können anschließend von allen anderen Usern verwendet werden..',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::SYSTEM_NOTIFICATION->value,
            'name_de' => "Systemnachrichten verwalten",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Systemnachrichten anlegen, editieren und löschen. Diese Benachrichtigungen werden allen Usern angezeigt.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::MA_MANAGER->value,
            'name_de' => "MA-Verwaltung",
            'group' => 'MA-Einstellungen',
            'tooltipText' => 'Darf MA Seiten anlegen und bearbeiten, aber die User nicht einladen.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::SHIFT_PLANNER->value,
            'name_de' => "Schichtplaner",
            'group' => 'MA-Einstellungen',
            'tooltipText' => 'Darf MA Seiten nicht anlegen aber die User verplanen.',
            'checked' => false
        ]);

        Role::create([
            'name' => RoleNameEnum::ARTWORK_ADMIN->value,
            'name_de' => "artwork-Admin",
        ]);

        Permission::create([
            'name' => PermissionNameEnum::VIEW_BUDGET_TEMPLATES->value,
            'name_de' => "Budgetvorlagen einsehen",
            "checked" => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::UPDATE_BUDGET_TEMPLATES->value,
            'name_de' => "Budgetvorlagen bearbeiten",
            "checked" => false
        ]);

        //not implemented
        Permission::create([
            'name' => PermissionNameEnum::GLOBAL_DOCUMENT_ADMIN->value,
            'name_de' => "Gloables Dokumentenmanagement",
            'tooltipText' => 'Darf alle Budget-Dokumente & Verträge von allen Projekten einsehen, bearbeiten und löschen',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
            'name_de' => "Globaler Budgetzugriff",
            'tooltipText' => 'Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen Projekten einsehen',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::VIEW_SHIFT_PLAN->value,
            'name_de' => "Schichtplan einsehen",
            'tooltipText' => 'Darf den globalen Schichtplan einsehen',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::CAN_COMMIT_SHIFTS->value,
            'name_de' => "Dienstpläne festschreiben",
            'tooltipText' => 'Darf Dienstpläne festschreiben',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::EDIT_EXTERNAL_USERS_CONDITIONS->value,
            'name_de' => "Externe Mitarbeiterkonditionen verwalten",
            'tooltipText' => 'Darf die Konditionen von externen Mitarbeitern sehen und bearbeiten',
            'checked' => false
        ]);



        /*$user = Role::create([
            'name' => RoleNameEnum::USER->value,
            'name_de' => "Standard-Nutzer*in",
        ]);*/

        //$user->syncPermissions([PermissionNameEnum::PROJECT_VIEW->value, PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value, PermissionNameEnum::EVENT_REQUEST->value]);

       /* $roomAdmin = Role::create([
            'name' => RoleNameEnum::ROOM_ADMIN->value,
            'name_de' => "Disponent*in",
        ]);

        //$roomAdmin->syncPermissions([PermissionNameEnum::ROOM_UPDATE->value]);

        $budgetAdmin = Role::create([
            'name' => RoleNameEnum::BUDGET_ADMIN->value,
            'name_de' => "Budgetadmin",
        ]);*/

        //$budgetAdmin->syncPermissions([PermissionNameEnum::PROJECT_BUDGET_ADMIN->value, PermissionNameEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE->value]);


        /*$contractAdmin = Role::create([
            'name' => RoleNameEnum::CONTRACT_ADMIN->value,
            'name_de' => "Vertragsadmin",
        ]);*/

        //$contractAdmin->syncPermissions([PermissionNameEnum::CONTRACT_EDIT_UPLOAD->value, PermissionNameEnum::PROJECT_BUDGET_SEE_DOCS_CONTRACTS->value]);

        /*$moneySourceAdmin = Role::create([
            'name' => RoleNameEnum::MONEY_SOURCE_ADMIN->value,
            'name_de' => "Finanzierungsquellenadmin",
        ]);*/

        //$moneySourceAdmin->syncPermissions([PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value]);


        // Gibt es nicht mehr
        /*Permission::create([
            'name' => PermissionNameEnum::PROJECT_DELETE->value,
            'name_de' => "Löschrechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value,
            'name_de' => "Finanzierungsquellen anlegen und verwalten",
            'group' => 'Finanzierungsquellen',
            'tooltipText' => 'User darf eigene Finanzierungsquellen anlegen und zur Einsicht & Verwaltung von Finanzierungsquellen eingeladen werden.',
            'checked' => false
        ]);*/





        //System
        //Tool
        /*Permission::create([
            'name' => PermissionNameEnum::PROJECT_SETTINGS_ADMIN->value,
            'name_de' => "Tooleinstellungen editieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf die Grundeinstellungen des Tools editieren und z.B. Logos austauschen, Impressum definieren etc.',
            'checked' => false
        ]);*/
        //Users

        //Teams

        //Projektsettings

        //Termine
        /*Permission::create([
            'name' => PermissionNameEnum::EVENT_TYPE_SETTINGS_ADMIN->value,
            'name_de' => "Systemeinstellungen für Termine definieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Termintypen definieren, editieren & löschen.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::EVENT_REQUEST->value,
            'name_de' => "Raumbelegungen anfragen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die eigenen Anfragen editieren & löschen.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::EVENT_REQUEST_CONFIRM->value,
            'name_de' => "Belegungs-Anfragen einsehen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf sämtliche Belegungsanfragen im Kalender einsehen. Auf diese Weise können Doppel-Anfragen vermieden werden.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::GLOBAL_NOTIFICATION_ADMIN->value,
            'name_de' => "Benachrichtigung für alle editieren",
            'group' => 'Sonstiges',
            'tooltipText' => 'Nutzer*in darf die Benachrichtigung für alle erstellen, bearbeiten und löschen.',
            'checked' => false
        ]);*/


        //Has every permission because of the gate in AuthServiceProvider



        //Globale
        /*Permission::create([
            'name' => PermissionNameEnum::ARTWORK_ADMIN->value,
            'name_de' => "artwork-Admin",
            'group' => 'Globale Rollen',
            'tooltipText' => 'Hat im gesamten System Schreibrecht.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::BUDGET_ADMIN->value,
            'name_de' => "Budgetadmin",
            'group' => 'Globale Rollen',
            'tooltipText' => 'Hat auf alle Projekt-Budgets Schreibrechte.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::CONTRACT_ADMIN->value,
            'name_de' => "Vertragsadmin",
            'group' => 'Globale Rollen',
            'tooltipText' => 'Hat Zugriff auf alle Verträge.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::MONEY_SOURCE_ADMIN->value,
            'name_de' => "Finanzierungsquellenadmin",
            'group' => 'Globale Rollen',
            'tooltipText' => 'HHat Schreibrecht auf alle Finanzierungsquellen.',
            'checked' => false
        ]);*/
    }
}
