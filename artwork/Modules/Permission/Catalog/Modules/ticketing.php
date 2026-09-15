<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Enums\PermissionEnum;

return new PermissionModuleDefinition(
    key: 'ticketing',
    title: 'artwork tickets',
    icon: 'IconBuildingStore',
    navOrder: 125,
    moduleSetting: null,
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::TICKETING_MANAGE,
            title: 'Manage artwork tickets',
            effect: 'Can connect this artwork to artwork tickets and change the ticketing settings',
            unlocks: ['"artwork tickets" in the settings'],
            allows: [
                'Connect and disconnect the ticket house',
            ],
            personas: [Persona::SYSADMIN],
        ),
    ],
);
