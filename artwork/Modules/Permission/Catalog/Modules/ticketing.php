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
            title: 'Change dates on sale',
            effect: 'Moves or withdraws dates on sale',
            allows: [
                'Move a date on sale after confirming it',
                'Withdraw a date on sale; sold tickets are cancelled',
            ],
            personas: [Persona::SYSADMIN],
            note: 'Without it, dates on sale can neither be moved nor withdrawn',
        ),
    ],
);
