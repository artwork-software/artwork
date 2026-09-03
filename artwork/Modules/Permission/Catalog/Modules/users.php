<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('users', 'Module "Staff" enabled');

return new PermissionModuleDefinition(
    key: 'users',
    title: 'Staff',
    icon: 'IconUsers',
    navOrder: 40,
    moduleSetting: 'users',
    hint: 'Everyone sees the staff list and edits their own profile. '
        . 'These permissions concern other people\'s data, invitations, external workers and teams.',
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::MA_MANAGER,
            title: 'HR administration',
            effect: 'Can maintain profiles, contracts and working times and invite people',
            unlocks: [
                'Button "Invite new users"',
                'Photo, basic data and team assignment in other people\'s profiles',
                'Tabs "Work profile", "Work Time Pattern", "Employment contract", "Work Times" and "Overtime" in the person profile',
                'Creating freelancers and service providers',
                'Tabs "Conditions" and "Work profile" of freelancers and service providers',
                'Rosters of other people',
            ],
            allows: [
                'Send and withdraw invitations',
                'Edit photo, basic data and team assignment of other people',
                'Edit work profiles, work time patterns, contracts, working times and overtime',
                'Create freelancers and service providers and maintain their absences',
                'Read and edit conditions and hourly rates of external workers',
            ],
            requires: [$module],
            personas: [Persona::HR],
            note: 'Paying out overtime additionally requires "Pay out overtime". '
                . 'Assigning permissions to other people remains reserved for artwork admins.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::TEAM_UPDATE,
            title: 'Team administration',
            effect: 'Can create and maintain teams',
            unlocks: ['Tab "Teams" in the staff area'],
            allows: [
                'Create, edit and delete teams',
                'Add and remove team members',
            ],
            requires: [$module],
            personas: [Persona::HR],
            note: 'Does not allow editing other people\'s profile data – that belongs to "HR administration".',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO,
            title: 'View private contact details',
            effect: 'Can see e-mail and phone marked as private',
            unlocks: [
                'Contact fields in the person popover',
                'Profile pages of freelancers and service providers',
            ],
            allows: ['Read only'],
            personas: [Persona::HR, Persona::SHIFT_PLANNING],
            note: 'Second effect: opens the profile pages of freelancers and service providers even without duty roster permissions.',
        ),
    ],
);
