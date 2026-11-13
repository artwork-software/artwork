<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

return new PermissionModuleDefinition(
    key: 'system',
    title: 'System settings',
    icon: 'IconSettings',
    navOrder: 110,
    moduleSetting: null,
    hint: 'Each of these permissions opens its own entry in the menu "System". None of them depends on a module switch.',
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::SETTINGS_UPDATE,
            title: 'Tool settings',
            effect: 'Can manage branding, mail, interfaces and module visibility',
            unlocks: [
                'Menu "System → Tool Settings" with the tabs "Branding", "External user management", "Communication & Legal", "Interfaces", "Module visibility", "File settings" and "Mail"',
                'Tab "BI Field Settings" in the project settings',
            ],
            allows: [
                'Logos, colours and legal texts',
                'Mail server, API tokens, Sage configuration and LDAP',
                'Switch modules on and off for the whole instance',
                'Allowed file types and sizes',
            ],
            personas: [Persona::SYSADMIN],
            note: 'Switching a module off hides it for everyone, regardless of their permissions.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::PROJECT_SETTINGS_UPDATE,
            title: 'Project settings',
            effect: 'Can manage categories, genres, sectors, status, tabs and components',
            unlocks: [
                'Menu "System → Projects" with all tabs',
                'Tabs "Tab Settings", "Component Settings", "Print Layout Settings", "Project Role Settings" and "Project overview builder"',
            ],
            allows: [
                'Categories, genres, sectors and project status',
                'Project tabs, components and print layouts',
                'Project roles and the project overview builder',
                'Artist residency settings',
            ],
            personas: [Persona::SYSADMIN, Persona::PRODUCTION_LEAD],
            note: 'The tabs "BI Field Settings" and "BI Export" in the same menu additionally require "Tool settings" or "Export BI data".',
        ),
        new PermissionDefinition(
            name: PermissionEnum::EVENT_SETTINGS_UPDATE,
            title: 'Event settings',
            effect: 'Can manage event types, status, properties, holidays and BI tags',
            unlocks: [
                'Menu "System → Events" with the tabs "Event Types", "BI Tags", "Standard values", "Public holidays & school holidays", "Event Status", "Event properties" and "Timeline Presets"',
            ],
            allows: [
                'Create, edit and delete event types and event status',
                'Event properties, standard values and timeline presets',
                'Public and school holidays, BI tags',
            ],
            personas: [Persona::DISPOSITION],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CALENDAR_SETTINGS_UPDATE,
            title: 'Calendar settings',
            effect: 'Can change the calendar settings',
            unlocks: ['Menu "System → Calendar"'],
            allows: [
                'Hours shown in the day view of calendar and duty roster',
                'Activate the day remarks column and set its visibility',
            ],
            personas: [Persona::DISPOSITION, Persona::SYSADMIN],
            note: 'Previously reserved for artwork admins.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::ROOM_UPDATE,
            title: 'Room administration',
            effect: 'Can manage rooms and areas',
            unlocks: [
                'Menu "System → Rooms"',
                'Admin actions on room pages',
                'Room capacities',
            ],
            allows: [
                'Create, edit, delete and sort rooms and areas',
                'Room admins, requesting persons and "bookable for all"',
            ],
            personas: [Persona::DISPOSITION],
            note: 'Alternatively, a person can be entered as room admin on a single room and then administers only that room.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CHECKLIST_SETTINGS_ADMIN,
            title: 'Checklist templates',
            effect: 'Can manage templates for to-do lists',
            unlocks: ['Menu "System → Checklists"'],
            allows: [
                'Create, edit and delete templates',
                'Edit and delete to-do lists of others',
            ],
            implies: [PermissionEnum::CHECKLIST_EDIT_PERMISSION],
            personas: [Persona::PRODUCTION_LEAD],
            note: 'Includes "Manage all to-do lists" from the module "To-dos".',
        ),
        new PermissionDefinition(
            name: PermissionEnum::MONEY_SOURCE_SETTINGS_UPDATE,
            title: 'Sources of funding settings',
            effect: 'Can manage the categories of the sources of funding',
            unlocks: ['Menu "System → Sources of funding"'],
            allows: ['Create and delete source categories'],
            requires: [Requirement::module('sources_of_funding', 'Module "Sources of funding" enabled')],
            personas: [Persona::FINANCE],
            note: 'Previously reserved for artwork admins. The sources of funding themselves are managed with the permissions of the module "Budget & funding".',
        ),
        new PermissionDefinition(
            name: PermissionEnum::BUDGET_SETTINGS_UPDATE,
            title: 'Budget settings',
            effect: 'Can manage general budget settings, accounts and cost units',
            unlocks: ['Menu "System → Budget" with the tabs "General", "Account management" and "Budget templates"'],
            allows: [
                'General budget settings',
                'Accounts and cost units',
            ],
            personas: [Persona::FINANCE],
            note: 'Previously reserved for artwork admins. The tab "Budget templates" additionally requires the budget template permissions of the module "Budget & funding".',
        ),
        new PermissionDefinition(
            name: PermissionEnum::TRASH_ACCESS,
            title: 'Open trash',
            effect: 'Can open the recycle bin',
            unlocks: ['Menu "Recycle bin"'],
            allows: ['Restore and permanently delete entries of the visible tabs'],
            personas: [Persona::SYSADMIN, Persona::PRODUCTION_LEAD],
            note: 'Previously reserved for artwork admins. Which tabs are shown is still decided by the module permissions: delete projects, delete inventory articles, manage CRM, room administration, global budget access, Sage permissions.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::SYSTEM_NOTIFICATION,
            title: 'System notifications',
            effect: 'Can maintain the message to all people',
            unlocks: ['Button "Change notification to all" on the page "Notifications"'],
            allows: ['Create, edit and delete the message to all'],
            personas: [Persona::SYSADMIN],
            note: 'There is no entry in the menu "System" for this; the button sits on the notifications page.',
        ),
    ],
    adminOnly: [],
);
