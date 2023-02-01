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

        //Projekte
        Permission::create([
            'name' => PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
            'name_de' => "Eigene Projekte anlegen & bearbeiten",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf Projekte anlegen, bearbeiten & löschen – dadurch ist er/sie automatisch Projektadmin des neu angelegten Projekts.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::PROJECT_VIEW->value,
            'name_de' => "Leserechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf sämtliche Projekte einsehen – sowohl die Projektdetails als auch die Belegungen im Kalender.',
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
            'name' => PermissionNameEnum::ROOM_REQUEST_READING_DETAILS->value,
            'name_de' => "Lesezugriff auf Details von Raumbelegungs-Anfragen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf sämtliche Belegungsanfragen im Kalender einsehen. Auf diese Weise können Doppel-Anfragen vermieden werden.',
            'checked' => false
        ]);

        Permission::create([
            'name' => PermissionNameEnum::ROOM_REQUEST_CONFIRM->value,
            'name_de' => "Raumbelegungen bestätigen, priorisieren & bearbeiten",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf sämtliche Belegungsanfragen im Kalender einsehen. Auf diese Weise können Doppel-Anfragen vermieden werden.',
            'checked' => false
        ]);

        // Dokumente & Budget
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
            'group' => 'Finanzierungsquellen',
            'tooltipText' => 'User darf eigene Finanzierungsquellen anlegen und zur Einsicht & Verwaltung von Finanzierungsquellen eingeladen werden.',
            'checked' => false
        ]);

        // Systemeinstellungen
        Permission::create([
            'name' => PermissionNameEnum::USER_UPDATE->value,
            'name_de' => "Nutzer*innen einladen, bearbeiten, Nutzerrechte definieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf weitere Nutzer*innen einladen, bearbeiten und löschen. Zusätzlich darf er/sie Nutzerrechte für sämtliche Nutzer*innen vergeben und editieren.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::TEAM_UPDATE->value,
            'name_de' => "Teams anlegen & bearbeiten",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Teams (Abteilungen) im System anlegen, editieren & löschen. Diese Teams können anschließend z.B. Projekten zugeordnet werden.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::SETTINGS_UPDATE->value,
            'name_de' => "Projektkategorien, Genres etc. definieren & bearbeiten",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche definieren, bearbeiten & löschen.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::ROOM_ADMIN->value,
            'name_de' => "Räume & Areale anlegen & bearbeiten",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf alle Räume & Areale anlegen, bearbeiten und löschen. Nutzer*in hat für sämtliche Räume Raumadminrechte – darf
             also Belegungsanfragen zusagen oder ablehnen. Er/sie darf zusätzlich Projekte in andere Räume verlegen und Räume direkt buchen – ohne vorherige Anfrage.
              Nutzer*in darf Raumadmin-Rechte für einzelne Räume an andere Nutzer*innen vergeben.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN->value,
            'name_de' => "Globale Checklisten-Vorlagen erstellen & bearbeiten",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen können anschließend von allen anderen Usern verwendet werden.',
            'checked' => false
        ]);

        Role::create([
            'name' => RoleNameEnum::ARTWORK_ADMIN->value,
            'name_de' => "artwork-Admin",
        ]);

        Role::create([
            'name' => RoleNameEnum::BUDGET_ADMIN->value,
            'name_de' => "Budgetadmin",
        ]);

        Role::create([
            'name' => RoleNameEnum::CONTRACT_ADMIN->value,
            'name_de' => "Vertragsadmin",
        ]);

        Role::create([
            'name' => RoleNameEnum::MONEY_SOURCE_ADMIN->value,
            'name_de' => "Finanzierungsquellenadmin",
        ]);

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
