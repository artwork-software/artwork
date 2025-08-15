<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
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

        ];

        foreach ($permissions as $permission) {
            $checkPermission = Permission::where('name', $permission['name'])->first();
            if (!$checkPermission) {
                Permission::create($permission);
                $this->info('Permission "' . $permission['name'] . '" created.');
            } else {
                // Update existing permission with new tooltip texts
                $checkPermission->update([
                    'tooltipText' => $permission['tooltipText'],
                    'tooltipKey' => $permission['tooltipKey']
                ]);
                $this->info('Permission "' . $permission['name'] . '" updated.');
            }
        }
    }
}
