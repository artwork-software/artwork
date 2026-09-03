<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

return new PermissionModuleDefinition(
    key: 'tasks',
    title: 'To-dos',
    icon: 'IconListCheck',
    navOrder: 100,
    moduleSetting: 'tasks',
    hint: 'Every person creates, edits and deletes their own to-do lists without any permission. '
        . '"Manage checklist templates" in the system settings includes this permission.',
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::CHECKLIST_EDIT_PERMISSION,
            title: 'Manage all to-do lists',
            effect: 'Can edit and delete other people\'s to-do lists',
            unlocks: [
                'Opening every to-do list',
                '"Delete" in the menu of other people\'s lists',
            ],
            allows: ['Change and delete lists of other people'],
            requires: [Requirement::module('tasks', 'Module "To-dos" enabled')],
            personas: [Persona::PRODUCTION_LEAD],
            note: 'Lists shared with the person or containing tasks assigned to them are editable without this permission.',
        ),
    ],
);
