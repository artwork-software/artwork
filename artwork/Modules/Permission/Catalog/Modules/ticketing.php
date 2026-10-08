<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Enums\PermissionEnum;

return new PermissionModuleDefinition(
    key: 'ticketing',
    title: 'Artwork-Tickets',
    icon: 'IconBuildingStore',
    navOrder: 125,
    moduleSetting: null,
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::TICKETING_MANAGE,
            title: 'Manage Artwork-Tickets',
            effect: 'Can connect this artwork to Artwork-Tickets and change the ticketing settings',
            unlocks: ['"Artwork-Tickets" in the settings'],
            allows: [
                'Connect and disconnect the ticket house',
            ],
            personas: [Persona::SYSADMIN],
        ),
        new PermissionDefinition(
            name: PermissionEnum::TICKETING_MOVE_ON_SALE,
            title: 'Move dates on sale',
            effect: 'Changes time or room of dates on sale',
            allows: [
                'Move a date on sale after confirming it',
            ],
            personas: [Persona::SYSADMIN],
            note: 'Without it, the time and room of a date on sale are locked',
        ),
    ],
);
