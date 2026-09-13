<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$funding = Requirement::module('sources_of_funding', 'Module "Sources of funding" enabled');
$sage = Requirement::feature('sage_api', 'Sage interface enabled');
$budgetSettingsMenu = Requirement::permission(PermissionEnum::BUDGET_SETTINGS_UPDATE, hard: false);

return new PermissionModuleDefinition(
    key: 'budget',
    title: 'Budget & funding',
    icon: 'IconCurrencyEuro',
    navOrder: 70,
    moduleSetting: null,
    hint: 'Budget access per project is granted in the project team. These permissions additionally apply to all projects.',
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN,
            title: 'Global budget access',
            effect: 'Can read and edit the budgets of all projects',
            unlocks: [
                'Budget tab in every project',
                'Tabs "General" and "Account management" under "System → Budget"',
                'Tabs "Accounts" and "Cost Units" in the "Recycle bin"',
            ],
            allows: [
                'Read and write all project budgets',
                'Manage accounts and cost units',
                'Basic budget settings',
                'Comment on Sage bookings',
            ],
            requires: [
                Requirement::projectTeam('Opening a project additionally requires "Read all projects" or membership in its project team'),
                $budgetSettingsMenu,
            ],
            personas: [Persona::FINANCE],
            note: 'The menu "System → Budget" requires "Budget settings" (module "System settings"). Documents in the budget tab follow the release of the project files.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::PROJECT_BUDGET_VERIFIED_ADD_REMOVE,
            title: 'Remove verifications & locks',
            effect: 'Can verify budget positions and lock or unlock columns',
            unlocks: ['Verify and unlock menus in the budget table'],
            allows: ['Verify, revoke verifications, lock and unlock columns'],
            requires: [
                Requirement::projectTeam('Requires budget access in the project (global or via the project team)'),
            ],
            personas: [Persona::FINANCE],
        ),
        new PermissionDefinition(
            name: PermissionEnum::VIEW_BUDGET_TEMPLATES,
            title: 'View budget templates',
            effect: 'Can see budget templates',
            unlocks: ['Tab "Budget templates" under "System → Budget"'],
            allows: ['Read only'],
            requires: [$budgetSettingsMenu],
            personas: [Persona::FINANCE],
            note: 'The menu "System → Budget" requires "Budget settings" (module "System settings").',
        ),
        new PermissionDefinition(
            name: PermissionEnum::UPDATE_BUDGET_TEMPLATES,
            title: 'Edit budget templates',
            effect: 'Can edit budget templates',
            unlocks: ['Edit mode in the budget templates', 'Creating and deleting templates'],
            allows: ['Columns, positions and rows of templates'],
            requires: [
                Requirement::permission(PermissionEnum::VIEW_BUDGET_TEMPLATES),
                $budgetSettingsMenu,
            ],
            implies: [PermissionEnum::VIEW_BUDGET_TEMPLATES],
            personas: [Persona::FINANCE],
        ),
        new PermissionDefinition(
            name: PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD,
            title: 'Manage sources of funding',
            effect: 'Can see, create and edit sources of funding',
            unlocks: [
                'Menu "Sources of funding"',
                'Linking sources of funding in the budget tab',
            ],
            allows: [
                'Create, edit, duplicate and pin sources of funding',
                'Assign people and projects',
            ],
            requires: [$funding],
            personas: [Persona::FINANCE],
            note: 'The settings for sources of funding are a separate permission "Sources of funding settings" (module "System settings").',
        ),
        new PermissionDefinition(
            name: PermissionEnum::MONEY_SOURCE_EDIT_DELETE,
            title: 'Delete all sources of funding',
            effect: 'Can delete every source of funding',
            unlocks: ['"Delete" in the menu of every source of funding'],
            allows: ['Delete sources of funding'],
            requires: [$funding, Requirement::permission(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD)],
            implies: [PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD],
            personas: [Persona::FINANCE],
        ),
        new PermissionDefinition(
            name: PermissionEnum::VIEW_PROJECT_SAGE_DATA,
            title: 'Project Sage data',
            effect: 'Can see project-related Sage bookings',
            unlocks: [
                'Block "Project-related Sage data" in the budget tab',
                'Sage cells in the budget table',
                'Tab "Sage API data sets" in the "Recycle bin"',
            ],
            allows: ['Assign, delete and restore Sage bookings'],
            requires: [
                $sage,
                Requirement::projectTeam('Assigning requires budget access in the project'),
            ],
            personas: [Persona::FINANCE],
        ),
        new PermissionDefinition(
            name: PermissionEnum::VIEW_GLOBAL_SAGE_DATA,
            title: 'Global Sage data',
            effect: 'Can see Sage bookings without a project',
            unlocks: [
                'Block "Global Sage data" in the budget tab',
                'Tab "Sage API data sets" in the "Recycle bin"',
            ],
            allows: ['Assign, delete and restore Sage bookings'],
            requires: [
                $sage,
                Requirement::projectTeam('Assigning requires budget access in the project'),
            ],
            personas: [Persona::FINANCE],
        ),
    ],
);
