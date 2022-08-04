<?php

namespace Database\Seeders;

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

        //System

        //Tool
        Permission::create([
            'name' => 'change tool settings',
            'name_de' => "Tooleinstellungen editieren",
            'group' => 'System'
        ]);

        //Users
        Permission::create([
            'name' => 'invite users',
            'name_de' => "Nutzer*innen einladen",
            'group' => 'System'
        ]);
        Permission::create([
            'name' => 'view users',
            'name_de' => "Nutzer*innen sehen",
            'group' => 'System'
        ]);
        Permission::create([
            'name' => 'update users',
            'name_de' =>  "Nutzer*innen bearbeiten",
            'group' => 'System'
        ]);
        Permission::create([
            'name' => 'delete users',
            'name_de' =>  "Nutzer*innen löschen",
            'group' => 'System'
        ]);
        Permission::create([
            'name' => 'update user permissions',
            'name_de' => "Rechte ändern",
            'group' => 'System'
        ]);

        //Räume
        Permission::create([
            'name' => 'create rooms',
            'name_de' => "Räume anlegen",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'edit rooms',
            'name_de' => "Räume editieren",
            'group' => 'System'
        ]);

        //Areas
        Permission::create([
            'name' => 'create areas',
            'name_de' => "Areale anlegen",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'edit areas',
            'name_de' => "Areale editieren",
            'group' => 'System'
        ]);

        //occupancy requests
        Permission::create([
            'name' => 'confirm occupancy requests',
            'name_de' => "Belegungssanfragen bestätigen",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'reject occupancy requests',
            'name_de' => "Belegungssanfragen ablehnen",
            'group' => 'System'
        ]);

        //Projekt genre bereiche kategorien
        Permission::create([
            'name' => 'create project properties',
            'name_de' => "Projekteigenschaften definieren",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'update project properties',
            'name_de' => "Projekteigenschaften bearbeiten",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'delete project properties',
            'name_de' => "Projekteigenschaften löschen",
            'group' => 'System'
        ]);

        //Termine
        Permission::create([
            'name' => 'create event type',
            'name_de' => "Termintypen definieren",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'update event type',
            'name_de' => "Termintypen editieren",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'delete event type',
            'name_de' => "Termintypen löschen",
            'group' => 'System'
        ]);

        //Checklists
        Permission::create([
            'name' => 'create checklist template',
            'name_de' => "Checklisten-Vorlage erstellen",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'update checklist template',
            'name_de' => "Checklisten-Vorlage bearbeiten",
            'group' => 'System'
        ]);

        Permission::create([
            'name' => 'delete checklist template',
            'name_de' => "Checklisten-Vorlage löschen",
            'group' => 'System'
        ]);

        //Rooms/Occupancy
        Permission::create([
            'name' => 'request room occupancy',
            'name_de' => "Raumbelegung anfragen",
            'group' => 'Räume/Belegung'
        ]);

        Permission::create([
            'name' => 'view occupancy requests',
            'name_de' => "Belegungs-Anfragen einsehen",
            'group' => 'Räume/Belegung'
        ]);

        //Projekte
        Permission::create([
            'name' => 'view projects',
            'name_de' => "Leserechte für alle Projekte",
            'group' => 'Projekte'
        ]);

        Permission::create([
            'name' => 'create projects',
            'name_de' => "Eigene Projekte anlegen & bearbeiten",
            'group' => 'Projekte'
        ]);

        Permission::create([
            'name' => 'update projects',
            'name_de' => "Schreibrechte alle Projekte",
            'group' => 'Projekte'
        ]);

        Permission::create([
            'name' => 'delete projects',
            'name_de' => "Löschrecht alle Projekte",
            'group' => 'Projekte'
        ]);

        //Sichtbarkeit
        Permission::create([
            'name' => 'view system settings',
            'name_de' => "Systemeinstellungen einsehen",
            'group' => 'Sichtbarkeit'
        ]);

        //Has every permission because of the gate in AuthServiceProvider
        Role::create([
            'name' => 'admin',
            'name_de' => "Adminrechte",
        ]);

        Role::create([
            'name' => 'user',
            'name_de' => "Standarduser"
        ])->givePermissionTo([
            'request room occupancy',
            'view occupancy requests',
            'view projects'
        ]);


    }
}
