<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

return new PermissionModuleDefinition(
    key: 'projects',
    title: 'Projects',
    icon: 'IconGeometry',
    navOrder: 10,
    moduleSetting: 'projects',
    hint: 'Access to a single project is also granted by membership in its project team. '
        . 'The project list (name, status, period, team) is visible to everyone; these permissions control opening and editing.',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::PROJECT_VIEW,
            title: 'Read all projects',
            effect: 'Can open and read every project',
            unlocks: [
                'Opening every project tile',
                'All project tabs',
                'Project files and room documents',
                'Project role matrix export',
            ],
            allows: ['Read only – editing requires write permission in the project team or "Write all projects"'],
            requires: [Requirement::module('projects', 'Module "Projects" enabled')],
            personas: [Persona::BASIS],
            note: 'Without this permission the creating person is added to the project team automatically when creating a project; with it, they are not.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::WRITE_PROJECTS,
            title: 'Write all projects',
            effect: 'Can edit every project, even without being in the team',
            unlocks: [
                'All edit buttons in all project tabs',
                'Project team and basic data',
                '"Edit basic data" and "Duplicate" in the project list',
            ],
            allows: [
                'Edit everything in every project',
                'Component restrictions of the tab settings do not apply',
            ],
            requires: [Requirement::module('projects', 'Module "Projects" enabled')],
            implies: [PermissionEnum::PROJECT_VIEW],
            personas: [Persona::PRODUCTION_LEAD],
        ),
        new PermissionDefinition(
            name: PermissionEnum::PROJECT_DELETE,
            title: 'Delete all projects',
            effect: 'Can delete every project',
            unlocks: [
                'Multi-select mode in the project list',
                '"Move to trash" in the project menu',
                'Emptying the project trash',
            ],
            allows: ['Delete, bulk delete and permanently delete projects'],
            requires: [Requirement::module('projects', 'Module "Projects" enabled')],
            implies: [PermissionEnum::WRITE_PROJECTS],
            personas: [Persona::PRODUCTION_LEAD],
            note: 'A project can also be deleted by anyone with the "Delete" checkbox in its project team.',
        ),
    ],
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::ADD_EDIT_OWN_PROJECT,
            title: 'Create own projects',
            effect: 'Can create new projects',
            unlocks: [
                'Button "New project"',
                'Creating a project directly from the event dialog',
            ],
            allows: [
                'Create projects',
                'Maintain team and departments of the own project',
            ],
            requires: [Requirement::module('projects', 'Module "Projects" enabled')],
            personas: [Persona::BASIS, Persona::PRODUCTION_LEAD],
            note: 'The creating person can edit the project afterwards. Deleting requires "Delete all projects" or the "Delete" checkbox in the project team.',
        ),
        new PermissionDefinition(
            name: PermissionEnum::PROJECT_MANAGEMENT,
            title: 'Can be project manager',
            effect: 'Can be marked as project manager in a project team',
            unlocks: [
                'Selectable as project manager in the team dialog',
                'List of departments',
            ],
            allows: ['After being marked: write permission in that project'],
            requires: [
                Requirement::module('projects', 'Module "Projects" enabled'),
                Requirement::projectTeam('Takes effect only once the person is marked as project manager in a project team'),
            ],
            personas: [Persona::PRODUCTION_LEAD],
            note: 'Grants no access to and no write permission for other projects.',
        ),
    ],
);
