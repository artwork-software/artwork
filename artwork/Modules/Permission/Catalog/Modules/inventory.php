<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('inventory', 'Module "Inventory" enabled');

return new PermissionModuleDefinition(
    key: 'inventory',
    title: 'Inventory & material issues',
    icon: 'IconBuildingWarehouse',
    navOrder: 50,
    moduleSetting: 'inventory',
    hint: 'Tag releases ("restricted tags") additionally restrict editing per article.',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::INVENTORY_CREATE_EDIT,
            title: 'Create & edit inventory',
            effect: 'Can create and edit articles',
            unlocks: [
                'Button "Add Article"',
                'Edit mode in the article dialog',
            ],
            allows: ['Create and edit articles, their properties and file attachments'],
            requires: [$module],
            personas: [Persona::INVENTORY],
            note: 'The inventory list itself is visible to everyone as soon as the module is enabled.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::INVENTORY_DELETE,
            title: 'Delete inventory',
            effect: 'Can delete and restore articles',
            unlocks: [
                '"Delete" in the article dialog',
                'Tab "Articles" in the "Recycle bin"',
            ],
            allows: ['Move articles to the trash, restore and permanently delete them'],
            requires: [$module],
            implies: [PermissionEnum::INVENTORY_CREATE_EDIT],
            personas: [Persona::INVENTORY],
        ),
    ],
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::INVENTORY_DISPOSITION,
            title: 'Inventory disposition',
            effect: 'Can plan articles on events and manage material issues',
            unlocks: [
                'Menu "Article Planning" and "Material Issues"',
                'Material issues of all projects',
            ],
            allows: [
                'Plan articles on events',
                'Create, edit and delete internal and external material issues',
                'Confirm or decline returns, manage attachments',
            ],
            requires: [$module],
            personas: [Persona::INVENTORY, Persona::CRAFT_LEAD],
            note: 'Project team members with write permission can create and edit the material issues of their own project without this permission (project tab).',
        ),
        new PermissionDefinition(
            name: PermissionEnum::MATERIAL_ISSUE_LOG_VIEW,
            title: 'Material issue log',
            effect: 'Can see the change history of material issues',
            unlocks: ['Log icon on material issues and in the project tab'],
            allows: ['Read only'],
            requires: [
                $module,
                Requirement::permission(PermissionEnum::INVENTORY_DISPOSITION, hard: false),
            ],
            personas: [Persona::INVENTORY],
            note: 'The menu "Material Issues" requires "Inventory disposition"; in the project tab the log is reachable without it.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::SET_CREATE_EDIT,
            title: 'Create & edit sets',
            effect: 'Can create and edit material sets',
            unlocks: ['Menu "System → Inventory", tab "Material Sets"'],
            allows: ['Create and edit material sets'],
            personas: [Persona::INVENTORY],
        ),
        new PermissionDefinition(
            name: PermissionEnum::SET_DELETE,
            title: 'Delete sets',
            effect: 'Can delete material sets',
            unlocks: ['"Delete" in the material set list'],
            allows: ['Delete material sets'],
            implies: [PermissionEnum::SET_CREATE_EDIT],
            personas: [Persona::INVENTORY],
        ),
        new PermissionDefinition(
            name: PermissionEnum::INVENTORY_SETTINGS,
            title: 'Inventory settings',
            effect: 'Can manage categories, properties, status, tags and manufacturers',
            unlocks: ['Menu "System → Inventory" with all tabs'],
            allows: ['All inventory settings'],
            personas: [Persona::INVENTORY],
            note: 'Shows the tab "Material Sets" as well; creating or deleting sets still requires the set permissions.',
        ),
    ],
);
