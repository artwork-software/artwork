<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Enums\PermissionEnum;

return new PermissionModuleDefinition(
    key: 'interfaces',
    title: 'Interfaces',
    icon: 'IconPlugConnected',
    navOrder: 120,
    moduleSetting: null,
    hint: 'Sage permissions are listed under "Budget & funding"; the Sage configuration belongs to "Tool settings".',
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::WEBHOOKS_MANAGE,
            title: 'Manage webhooks',
            effect: 'Can manage webhook endpoints and their deliveries',
            unlocks: ['Tab "Interfaces" in the tool settings (webhook section only)'],
            allows: [
                'Create, edit and delete endpoints',
                'View signatures and the delivery log',
                'Redeliver failed deliveries',
            ],
            personas: [Persona::SYSADMIN],
            note: 'Deliberately decoupled from "Tool settings": the other sections of the tab "Interfaces" still require "Tool settings".',
        ),
    ],
);
