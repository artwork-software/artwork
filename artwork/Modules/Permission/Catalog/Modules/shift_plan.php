<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('shift_plan', 'Module "Duty roster" enabled');
$view = Requirement::permission(PermissionEnum::VIEW_SHIFT_PLAN);

$granularArea = static fn (
    PermissionEnum $view,
    PermissionEnum $edit,
    string $area,
    string $tab
): array => [
    new PermissionDefinition(
        name: $view,
        title: "View {$area}",
        effect: "Can see the tab \"{$tab}\" in the shift settings",
        unlocks: ["Tab \"{$tab}\" in the shift settings"],
        allows: ['Read only'],
        requires: [
            Requirement::permission(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT),
            Requirement::setting('shift_granular_permissions', 'Shift setting "Granular permissions" enabled'),
        ],
        defaultChecked: true,
    ),
    new PermissionDefinition(
        name: $edit,
        title: "Edit {$area}",
        effect: "Can edit the tab \"{$tab}\" in the shift settings",
        unlocks: ["Edit mode in the tab \"{$tab}\""],
        allows: ['Create, edit and delete entries'],
        requires: [
            Requirement::permission(PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT),
            Requirement::setting('shift_granular_permissions', 'Shift setting "Granular permissions" enabled'),
        ],
        implies: [$view],
        defaultChecked: true,
    ),
];

return new PermissionModuleDefinition(
    key: 'shift_plan',
    title: 'Duty roster',
    icon: 'IconCalendarUser',
    navOrder: 30,
    moduleSetting: 'shift_plan',
    hint: 'Which crafts a planning person may edit is set per craft in the shift settings.',
    advancedTitle: 'Fine-grained shift settings permissions',
    advancedHint: 'Only take effect with the shift setting "Granular permissions" enabled',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::VIEW_SHIFT_PLAN,
            title: 'View duty rosters',
            effect: 'Can see all duty rosters',
            unlocks: [
                'Menu "Duty rosters" and "Shift plan list view"',
                'Shift history',
                'Excel exports of the duty roster',
                'Rosters of other people',
            ],
            allows: ['Read only'],
            requires: [$module],
            personas: [Persona::SHIFT_PLANNING, Persona::CRAFT_LEAD],
        ),
        new PermissionDefinition(
            name: PermissionEnum::SHIFT_PLANNER,
            title: 'Plan shifts',
            effect: 'Can create shifts, assign people and manage compensation days',
            unlocks: [
                'Edit mode and multi-edit in the duty roster',
                'Menu "Compensation days overview"',
                'Tab "Substitute days off" in the person profile',
                'Binding project day assignments',
            ],
            allows: [
                'Create, edit and delete shifts',
                'Assign and remove people, apply shift templates',
                'Manage compensation days',
                'Decide work time change requests of the own crafts',
                'Set the availability status of people',
                'Confirm or decline shifts on behalf of others',
            ],
            requires: [$module, Requirement::permission(PermissionEnum::VIEW_SHIFT_PLAN)],
            implies: [PermissionEnum::VIEW_SHIFT_PLAN],
            personas: [Persona::SHIFT_PLANNING],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_COMMIT_SHIFTS,
            title: 'Commit duty rosters',
            effect: 'Can commit calendar weeks per craft',
            unlocks: ['Commit button in the duty roster'],
            allows: [
                'Commit and reopen calendar weeks per craft',
                'With the approval workflow enabled: submit a commit request',
            ],
            requires: [$module, $view],
            implies: [PermissionEnum::SHIFT_PLANNER],
            personas: [Persona::SHIFT_PLANNING],
            note: 'With the approval workflow enabled, approving is decided by the approver list in the shift settings, not by this permission.',
        ),
    ],
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::CAN_VIEW_OWN_ROSTER,
            title: 'View own roster',
            effect: 'Can see the own roster',
            unlocks: ['Menu "My operational plan"', 'PDF export of the own roster'],
            allows: ['Read only'],
            requires: [$module],
            personas: [Persona::BASIS],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_VIEW_OWN_UNCOMMITTED_SHIFTS,
            title: 'View own uncommitted shifts',
            effect: 'Can see own uncommitted shifts despite instance-wide hiding',
            allows: ['Read only'],
            requires: [
                $module,
                Requirement::permission(PermissionEnum::CAN_VIEW_OWN_ROSTER),
                Requirement::setting('hide_uncommitted_shifts', 'Shift setting "Hide uncommitted shifts from own roster" enabled'),
            ],
            personas: [Persona::CRAFT_LEAD],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_SUBSCRIBE_SHIFT_CALENDAR,
            title: 'Subscribe to roster calendar',
            effect: 'Can subscribe to the own roster as a calendar (ICS)',
            unlocks: ['Subscription icon in duty roster and own roster'],
            allows: ['Set up a calendar subscription'],
            requires: [$module],
            personas: [Persona::BASIS],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_VIEW_SHIFT_USER_KPIS,
            title: 'View staff info data',
            effect: 'Can open the info window per person in the duty roster',
            unlocks: ['Info icon on person tiles in the duty roster'],
            allows: ['Season key figures, compensation days, vacation, working times and overtime of all people'],
            requires: [$module, $view],
            personas: [Persona::SHIFT_PLANNING, Persona::HR],
            note: 'Working times and overtime require "View hour accounts" as well.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS,
            title: 'View hour accounts',
            effect: 'Can see working time accounts and weekly hours of others',
            unlocks: ['Hour columns in the daily view', 'Export "Craft distribution"'],
            allows: ['Read working time accounts and weekly hours of all people (own values are always visible)'],
            requires: [$module, $view],
            personas: [Persona::SHIFT_PLANNING],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_PAY_OUT_OVERTIME,
            title: 'Pay out overtime',
            effect: 'Can pay out overtime',
            unlocks: ['Pay-out button in the overtime tab'],
            allows: ['Book out minutes with date and comment'],
            requires: [
                $module,
                Requirement::permission(PermissionEnum::CAN_VIEW_SHIFT_USER_KPIS, hard: false),
                Requirement::permission(PermissionEnum::MA_MANAGER, hard: false),
            ],
            personas: [Persona::HR],
            note: 'Reachable via the info window ("View staff info data") or the person profile ("HR administration"). The person needs an active overtime rule.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::AVAILABILITY_MANAGEMENT,
            title: 'Manage availability of others',
            effect: 'Can maintain vacation, availability and individual times of other people',
            unlocks: ['Availability calendar in other people\'s profiles'],
            allows: ['Create, edit and delete vacation, availability and individual times', 'Set the availability status'],
            requires: [$module],
            personas: [Persona::SHIFT_PLANNING, Persona::HR],
        ),
        new PermissionDefinition(
            name: PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT,
            title: 'Shift settings',
            effect: 'Can manage crafts, functions, templates, rules and the workflow',
            unlocks: ['Menu "System → Shift settings"'],
            allows: ['All shift settings'],
            requires: [$module],
            personas: [Persona::SHIFT_PLANNING],
            note: 'With the shift setting "Granular permissions" enabled, the fine-grained permissions below decide per tab.',
        ),
    ],
    advanced: [
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_GENERAL_VIEW, PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT, 'general shift settings', 'General'),
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_DAY_SERVICES_VIEW, PermissionEnum::SHIFT_SETTINGS_DAY_SERVICES_EDIT, 'day services', 'Day services'),
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_VIEW, PermissionEnum::SHIFT_SETTINGS_WORK_TIME_PATTERNS_EDIT, 'work time patterns', 'Work time patterns'),
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_VIEW, PermissionEnum::SHIFT_SETTINGS_USER_CONTRACTS_EDIT, 'user contracts', 'Contracts'),
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_VIEW, PermissionEnum::SHIFT_SETTINGS_SHIFT_GROUPS_EDIT, 'shift groups', 'Shift groups'),
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_VIEW, PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_EDIT, 'shift templates', 'Shift templates'),
        ...$granularArea(PermissionEnum::SHIFT_SETTINGS_RULES_VIEW, PermissionEnum::SHIFT_SETTINGS_RULES_EDIT, 'shift rules', 'Shift rules'),
    ],
);
