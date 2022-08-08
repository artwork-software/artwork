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
            'group' => 'System',
            'tooltipText' => 'Nutzer*in darf die Grundeinstellungen des Tools editieren und z.B. Logos austauschen, Impressum definieren etc.'
        ]);
        //Users
        Permission::create([
            'name' => 'usermanagement',
            'name_de' => "Nutzer*innenverwaltung",
            'group' => 'System',
            'tooltipText' => 'Nutzer*in darf weitere Nutzer*innen einladen, bearbeiten und löschen. Zusätzlich darf er/sie Nutzerrechte für sämtliche Nutzer*innen vergeben und editieren.'
        ]);
        //Projektsettings
        Permission::create([
            'name' => 'admin projectSettings',
            'name_de' => "Systemeinstellungen für Projekte definieren",
            'group' => 'System',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche definieren, bearbeiten & löschen.'
        ]);
        //Termine
        Permission::create([
            'name' => 'admin eventTypeSettings',
            'name_de' => "Systemeinstellungen für Termine definieren",
            'group' => 'System',
            'tooltipText' => 'Nutzer*in darf in den Systemeinstellungen Termintypen definieren, editieren & löschen.'
        ]);
        //Checklistenvorlagen
        Permission::create([
            'name' => 'admin checklistTemplates',
            'name_de' => "Verwaltung von Checklisten-Vorlagen",
            'group' => 'System',
            'tooltipText' => 'Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen können anschließend von allen anderen Usern verwendet werden.'
        ]);
        //Rooms/Occupancy
        Permission::create([
            'name' => 'admin rooms',
            'name_de' => "Super-Raumadmin/Dispo",
            'group' => 'Räume/Belegung',
            'tooltipText' => 'Nutzer*in darf alle Räume & Areale anlegen, bearbeiten und löschen. Nutzer*in hat für sämtliche Räume Raumadminrechte – darf
             also Belegungsanfragen zusagen oder ablehnen. Er/sie darf zusätzlich Projekte in andere Räume verlegen und Räume direkt buchen – ohne vorherige Anfrage.
              Nutzer*in darf Raumadmin-Rechte für einzelne Räume an andere Nutzer*innen vergeben.'
        ]);
        Permission::create([
            'name' => 'request room occupancy',
            'name_de' => "Raumbelegungen anfragen",
            'group' => 'Räume/Belegung',
            'tooltipText' => 'Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die eigenen Anfragen editieren & löschen.'
        ]);
        Permission::create([
            'name' => 'view occupancy requests',
            'name_de' => "Belegungs-Anfragen einsehen",
            'group' => 'Räume/Belegung',
            'tooltipText' => 'Nutzer*in darf sämtliche Belegungsanfragen im Kalender einsehen. Auf diese Weise können Doppel-Anfragen vermieden werden.'
        ]);

        //Projekte
        Permission::create([
            'name' => 'view projects',
            'name_de' => "Leserechte für alle Projekte",
            'group' => 'Projekte'
        ]);
        Permission::create([
            'name' => 'create and edit projects',
            'name_de' => "Eigene Projekte anlegen & bearbeiten",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf Projekte anlegen, bearbeiten & löschen – dadurch ist er/sie automatisch Projektadmin des neu angelegten Projekts.'
        ]);
        Permission::create([
            'name' => 'admin projects',
            'name_de' => "Schreibrechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in hat auf alle Projekte Projektadmin-Rechte, auch wenn er/sie nicht zum Projektteam gehört.'
        ]);
        Permission::create([
            'name' => 'delete projects',
            'name_de' => "Löschrechte für alle Projekte",
            'group' => 'Projekte',
            'tooltipText' => 'Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört.'
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
