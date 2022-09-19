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
            'name' => PermissionNameEnum::PROJECT_VIEW,
            'name_de' => "Leserechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf sämtliche Projekte einsehen – sowohl die Projektdetails als auch die Belegungen im Kalender.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::PROJECT_UPDATE,
            'name_de' => "Eigene Projekte anlegen & bearbeiten",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf Projekte anlegen, bearbeiten & löschen – dadurch ist er/sie automatisch Projektadmin des neu angelegten Projekts.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::PROJECT_ADMIN,
            'name_de' => "Schreibrechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in hat auf alle Projekte Projektadmin-Rechte, auch wenn er/sie nicht zum Projektteam gehört.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::PROJECT_DELETE,
            'name_de' => "Löschrechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört.',
            'checked' => false
        ]);

        //System
        //Tool
        Permission::create([
            'name' => PermissionNameEnum::PROJECT_SETTINGS_ADMIN,
            'name_de' => "Tooleinstellungen editieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf die Grundeinstellungen des Tools editieren und z.B. Logos austauschen, Impressum definieren etc.',
            'checked' => false
        ]);
        //Users
        Permission::create([
            'name' => PermissionNameEnum::SETTINGS_UPDATE,
            'name_de' => "Nutzer*innenverwaltung",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf weitere Nutzer*innen einladen, bearbeiten und löschen. Zusätzlich darf er/sie Nutzerrechte für sämtliche Nutzer*innen vergeben und editieren.',
            'checked' => false
        ]);
        //Teams
        Permission::create([
            'name' => PermissionNameEnum::USER_UPDATE,
            'name_de' => "Teamverwaltung",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Teams (Abteilungen) im System anlegen, editieren & löschen. Diese Teams können anschließend z.B. Projekten zugeordnet werden.',
            'checked' => false
        ]);
        //Projektsettings
        Permission::create([
            'name' => PermissionNameEnum::TEAM_UPDATE,
            'name_de' => "Systemeinstellungen für Projekte definieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche definieren, bearbeiten & löschen.',
            'checked' => false
        ]);
        //Termine
        Permission::create([
            'name' => PermissionNameEnum::EVENT_TYPE_SETTINGS_ADMIN,
            'name_de' => "Systemeinstellungen für Termine definieren",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Termintypen definieren, editieren & löschen.',
            'checked' => false
        ]);
        //Checklistenvorlagen
        Permission::create([
            'name' => PermissionNameEnum::CHECKLIST_SETTINGS_ADMIN,
            'name_de' => "Verwaltung von Checklisten-Vorlagen",
            'group' => 'Systemeinstellungen',
            'tooltipText' => 'Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen können anschließend von allen anderen Usern verwendet werden.',
            'checked' => false
        ]);
        //Rooms/Occupancy
        Permission::create([
            'name' => PermissionNameEnum::ROOM_ADMIN,
            'name_de' => "Super-Raumadmin/Dispo",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf alle Räume & Areale anlegen, bearbeiten und löschen. Nutzer*in hat für sämtliche Räume Raumadminrechte – darf
             also Belegungsanfragen zusagen oder ablehnen. Er/sie darf zusätzlich Projekte in andere Räume verlegen und Räume direkt buchen – ohne vorherige Anfrage.
              Nutzer*in darf Raumadmin-Rechte für einzelne Räume an andere Nutzer*innen vergeben.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::EVENT_REQUEST,
            'name_de' => "Raumbelegungen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die eigenen Anfragen editieren & löschen.',
            'checked' => false
        ]);
        Permission::create([
            'name' => PermissionNameEnum::EVENT_REQUEST_CONFIRM,
            'name_de' => "Belegungs-Anfragen einsehen",
            'group' => 'Raumbelegungen',
            'tooltipText' => 'Nutzer*in darf sämtliche Belegungsanfragen im Kalender einsehen. Auf diese Weise können Doppel-Anfragen vermieden werden.',
            'checked' => false
        ]);

        //Has every permission because of the gate in AuthServiceProvider
        Role::create([
            'name' => RoleNameEnum::ADMIN,
            'name_de' => "Adminrechte",
        ]);

        Role::create([
            'name' => RoleNameEnum::USER,
            'name_de' => "Standarduser"
        ])->givePermissionTo([
            'request room occupancy',
            'view occupancy requests',
            'view projects',
        ]);
    }
}
