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
        ];

        foreach ($permissions as $permission) {
            $checkPermission = Permission::where('name', $permission['name'])->first();
            if (!$checkPermission) {
                Permission::create($permission);
                $this->info('Permission "' . $permission['name'] . '" created.');
            } else {
                $this->info('Permission "' . $permission['name'] . '" already exists.');
            }
        }
    }
}
