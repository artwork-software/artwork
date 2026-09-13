<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('crm', 'Module "CRM" enabled');

return new PermissionModuleDefinition(
    key: 'crm',
    title: 'CRM',
    icon: 'IconAddressBook',
    navOrder: 80,
    moduleSetting: 'crm',
    hint: 'Confidential property groups are released per department in the CRM settings; "Manage CRM" always sees all of them.',
    advancedTitle: 'Disabled features',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::CRM_VIEW,
            title: 'Use CRM',
            effect: 'Can read and maintain contacts',
            unlocks: [
                'Menu "CRM"',
                'Contact popover and artist linking in projects, residencies and inventory',
            ],
            allows: [
                'Read, create, edit and delete contacts',
                'Only released property groups are visible',
            ],
            requires: [$module],
            personas: [Persona::CRM],
            note: 'Reading and writing are deliberately combined; there is no read-only CRM permission.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CRM_MANAGER,
            title: 'Manage CRM',
            effect: 'Can manage contact types and properties and see all property groups',
            unlocks: [
                'Menu "System → CRM Settings"',
                'Import, export and duplicate check in the CRM',
                'Tab "CRM contacts" in the recycle bin',
            ],
            allows: [
                'Manage contact types, property groups and properties',
                'Import and export contacts, merge duplicates',
                'See all property groups, including confidential ones',
                'Restore and permanently delete contacts',
            ],
            requires: [$module],
            implies: [PermissionEnum::CRM_VIEW],
            personas: [Persona::CRM],
        ),
    ],
    advanced: [
        new PermissionDefinition(
            name: PermissionEnum::INVITE_EXTERNAL,
            title: 'Invite externals',
            effect: 'Can invite external people to maintain their own contact data',
            unlocks: ['Currently hidden – the external access feature is disabled'],
            allows: ['Send invitations and review external submissions'],
            requires: [
                $module,
                Requirement::feature('external_access', 'Feature "External access" enabled'),
            ],
            personas: [Persona::CRM],
            note: 'Has no effect as long as the external access feature is disabled.',
            hidden: true,
        ),
    ],
);
