<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('room_assignment', 'Module "Calendar" enabled');
$planningModule = Requirement::module('planning_calendar', 'Module "Planning calendar" enabled');
$dayRemarksSetting = Requirement::setting('day_remarks_enabled', 'Calendar setting "Day remarks" enabled');

return new PermissionModuleDefinition(
    key: 'calendar',
    title: 'Calendar & events',
    icon: 'IconCalendarClock',
    navOrder: 20,
    moduleSetting: 'room_assignment',
    hint: 'Without permissions a person can only book in rooms that are bookable for everyone '
        . 'or in which they are listed as requester or room admin.',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::EVENT_REQUEST,
            title: 'Request room occupancy',
            effect: 'Can request events in all rooms',
            unlocks: [
                'Button "+" in the calendar',
                'Event dialog in request mode',
            ],
            allows: [
                'Submit requests in all rooms',
                'Edit own events',
            ],
            requires: [$module],
            personas: [Persona::BASIS],
            note: 'Requests are not bound to a project. Own events stay editable because the person created them, '
                . 'not because of this permission.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST,
            title: 'Plan events directly',
            effect: 'Can book events directly and answer requests',
            unlocks: [
                'Tab "Incoming requests" under "Event Verifications"',
                'Direct booking in the event dialog',
                'Bulk creation and event series',
                '"Decline event" and "Delete" in the menu of other people\'s events',
                '"Save timeline as preset"',
            ],
            allows: [
                'Book events in all rooms without request',
                'Edit, decline and delete other people\'s events',
                'Accept or decline room requests',
            ],
            requires: [$module],
            implies: [PermissionEnum::EVENT_REQUEST, PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR],
            personas: [Persona::DISPOSITION, Persona::PRODUCTION_LEAD],
            note: 'Room admins have the same rights within their own rooms without this permission.',
        ),
    ],
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::CAN_SEE_PLANNING_CALENDAR,
            title: 'View planning calendar',
            effect: 'Can see the planning calendar and planned events',
            unlocks: [
                'Menu "Planning Calendar"',
                'Switch "Show planned events" in the calendar settings',
            ],
            allows: [
                'Create planned events (as request)',
                'Request verification of planned events',
            ],
            requires: [$planningModule],
            personas: [Persona::PRODUCTION_LEAD],
            note: 'When the module "Planning calendar" is disabled, the planning calendar is closed for artwork admins as well.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_EDIT_PLANNING_CALENDAR,
            title: 'Edit planned events',
            effect: 'Can edit, move, duplicate and delete planned events',
            unlocks: [
                'Action bar in the planning calendar',
                '"Convert to planned event" in the event menu',
                'Bulk creation of planned events',
            ],
            allows: [
                'Edit, move, duplicate and delete planned events',
                'Convert all events of a project to planning',
            ],
            requires: [$planningModule, Requirement::permission(PermissionEnum::CAN_SEE_PLANNING_CALENDAR)],
            implies: [PermissionEnum::CAN_SEE_PLANNING_CALENDAR],
            personas: [Persona::DISPOSITION],
            note: 'Verifying planned events does not depend on this permission but on the verifier assignment of the event type.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::CAN_PLAN_FIXED_IN_PLANNING_CALENDAR,
            title: 'Plan directly in the planning calendar',
            effect: 'Can book planned events without a request',
            unlocks: ['Direct booking in the planned event dialog'],
            allows: ['Only when creating – no editing of existing planned events'],
            requires: [$planningModule, Requirement::permission(PermissionEnum::CAN_SEE_PLANNING_CALENDAR)],
            personas: [Persona::DISPOSITION],
        ),
        new PermissionDefinition(
            name: PermissionEnum::DAY_REMARKS_VIEW,
            title: 'View day remarks',
            effect: 'Can see the day remarks',
            unlocks: [
                'Column "Day remarks" in calendar, planning calendar and duty roster',
                'Option "Day remarks" in the PDF export',
            ],
            allows: ['Read only'],
            requires: [$dayRemarksSetting],
            personas: [Persona::BASIS],
            defaultChecked: true,
        ),
        new PermissionDefinition(
            name: PermissionEnum::DAY_REMARKS_EDIT,
            title: 'Edit day remarks',
            effect: 'Can create, edit and delete day remarks',
            unlocks: ['Edit mode in the column "Day remarks"'],
            allows: ['Create, change and delete day remarks (one remark per day)'],
            requires: [$dayRemarksSetting],
            implies: [PermissionEnum::DAY_REMARKS_VIEW],
            personas: [Persona::DISPOSITION],
        ),
    ],
);
