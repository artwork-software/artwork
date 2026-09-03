<?php

use Artwork\Modules\Permission\Catalog\PermissionDefinition;
use Artwork\Modules\Permission\Catalog\PermissionModuleDefinition;
use Artwork\Modules\Permission\Catalog\Persona;
use Artwork\Modules\Permission\Catalog\Requirement;
use Artwork\Modules\Permission\Enums\PermissionEnum;

$module = Requirement::module('contracts', 'Module "Documents" enabled');

return new PermissionModuleDefinition(
    key: 'contracts',
    title: 'Documents & contracts',
    icon: 'IconFileText',
    navOrder: 60,
    moduleSetting: 'contracts',
    hint: 'Access to individual project contracts and documents depends on the release on the document or the project team, not on these permissions.',
    tiers: [
        new PermissionDefinition(
            name: PermissionEnum::CONTRACT_SEE_DOWNLOAD,
            title: 'View contract modules',
            effect: 'Can see and download contract modules',
            unlocks: [
                'Menu "Documents → Contracts"',
                'Download icon on contract modules',
            ],
            allows: ['Read and download contract modules'],
            requires: [$module],
            personas: [Persona::BASIS],
        ),
        new PermissionDefinition(
            name: PermissionEnum::CONTRACT_EDIT_UPLOAD,
            title: 'Manage contract modules & project contracts',
            effect: 'Can upload contract modules and maintain project contracts',
            unlocks: [
                'Upload and delete on contract modules',
                'Contract area in the project budget tab',
                'Menu "Documents → Document requests"',
            ],
            allows: [
                'Upload and delete contract modules',
                'Create project contracts',
            ],
            requires: [$module],
            implies: [PermissionEnum::CONTRACT_SEE_DOWNLOAD],
            personas: [Persona::CONTRACTS],
        ),
    ],
    extras: [
        new PermissionDefinition(
            name: PermissionEnum::DOCUMENT_REQUEST_CREATE,
            title: 'Create document requests',
            effect: 'Can create and assign document requests',
            unlocks: [
                'Menu "Documents → Document requests"',
                'Button "Create document request" (also in the project tab)',
                'Tab "Unassigned" in the document requests',
            ],
            allows: ['Create and assign document requests'],
            requires: [$module],
            personas: [Persona::PRODUCTION_LEAD],
        ),
        new PermissionDefinition(
            name: PermissionEnum::DOCUMENT_REQUEST_EDIT,
            title: 'Edit document requests',
            effect: 'Can edit document requests and change their status',
            unlocks: [
                'Menu "Documents → Document requests"',
                '"Edit" and "Delete" in the menu of a document request',
            ],
            allows: ['Edit, set the status, delete and link a contract'],
            requires: [$module],
            personas: [Persona::CONTRACTS],
            note: 'Requests assigned to the person can always be answered by uploading the document, without this permission.',
        ),
    ],
);
