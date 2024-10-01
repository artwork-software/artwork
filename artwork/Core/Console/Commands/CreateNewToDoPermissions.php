<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Illuminate\Console\Command;

class CreateNewToDoPermissions extends Command
{
    protected $signature = 'artwork:create-new-to-do-permissions';
    protected $description = 'Create new Permissions for ToDos';

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
