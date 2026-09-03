<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('business_intelligence', 'Module "Business Intelligence" enabled');

return new PermissionModuleDefinition(
    key: 'business_intelligence',
    title: 'Business Intelligence',
    icon: 'IconChartHistogram',
    navOrder: 90,
    moduleSetting: 'business_intelligence',
    hint: 'The BI component in the project tab depends on access to the project, not on these permissions.',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::BI_DASHBOARD,
            title: 'View BI dashboard',
            effect: 'Can open the house-wide BI dashboard',
            unlocks: ['Menu "BI Dashboard"'],
            allows: ['Key figures and period comparisons across all projects'],
            requires: [$module],
            personas: [Persona::FINANCE, Persona::PRODUCTION_LEAD],
        ),
        new PermissionDefinition(
            name: PermissionEnum::BI_EXPORT,
            title: 'Export BI data',
            effect: 'Can export BI and booking data as Excel',
            unlocks: [
                'Export buttons in the BI dashboard and in the project BI component',
                'House-wide booking export by account and cost unit',
            ],
            allows: [
                'Configure exports and save export presets',
                'Export BI data of all projects — also of projects without personal project access',
                'Export bookings house-wide, independent of a project',
            ],
            requires: [
                $module,
                Requirement::permission(PermissionEnum::PROJECT_SETTINGS_UPDATE, hard: false),
            ],
            implies: [PermissionEnum::BI_DASHBOARD],
            personas: [Persona::FINANCE],
        ),
    ],
);
